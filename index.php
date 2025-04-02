<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Configurazione e inclusione delle API
include_once "conn/config.php";
include_once "conn/models/SongsController.php";
include_once "conn/models/ArtistController.php";
include_once "conn/models/InterpretaController.php";

$conn = createConnection();

$songsController = new SongsController($conn);
$artistsController = new ArtistsController($conn);
$interpretaController = new InterpretaController($conn);

// Gestione azioni
$action = $_GET["action"] ?? "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    switch ($action) {
        case "add_song":
            // Recupera i dati della canzone
            $songData = [
                "titolo" => $_POST["titolo"],
                "durata" => $_POST["durata"],
                "anno" => $_POST["anno"],
                "genere" => $_POST["genere"],
                "autore" => "some autor", // Setting a default value
            ];

            // Crea la canzone e ottieni l'ID
            $songId = $songsController->createSong($songData);

            // Check if song creation was successful
            if ($songId) {
                // Associa i cantanti alla canzone
                if (isset($_POST["cantanti"]) && is_array($_POST["cantanti"])) {
                    foreach ($_POST["cantanti"] as $cantanteId) {
                        $interpretaController->addInterpretazione(
                            $songId,
                            $cantanteId
                        );
                    }
                }
                $message = "Canzone aggiunta con successo!";
            } else {
                $message = "Errore nell'aggiunta della canzone.";
            }
            break;

        case "update_song":
            // Get the song ID from the URL
            $songId = $_GET["id"];

            // Retrieve song data from the form
            $songData = [
                "titolo" => $_POST["titolo"],
                "durata" => $_POST["durata"],
                "anno" => $_POST["anno"],
                "genere" => $_POST["genere"],
            ];

            // Update the song
            $result = $songsController->updateSong($songId, $songData);

            if ($result) {
                // Update interpretations
                $interpretaController->removeInterpretazioniByCanzone($songId); // Remove existing interpretations
                if (isset($_POST["cantanti"]) && is_array($_POST["cantanti"])) {
                    foreach ($_POST["cantanti"] as $cantanteId) {
                        $interpretaController->addInterpretazione(
                            $songId,
                            $cantanteId
                        ); // Add new interpretations
                    }
                }
                $message = "Canzone aggiornata con successo!";
            } else {
                $message = "Errore nell'aggiornamento della canzone.";
            }
            break;

        case "add_artist":
            // Create artist using data from the form
            $artistData = [
                "nome" => $_POST["nome"],
                "cognome" => $_POST["cognome"],
                "data_nascita" => $_POST["data_nascita"],
                "nazionalità" => $_POST["nazionalità"],
            ];
            $result = $artistsController->createArtist($artistData);
            $message = $result
                ? "Artista aggiunto con successo!"
                : "Errore nell'aggiunta dell'artista.";
            break;

        case "delete": // Changed action to "delete"
            if ($_POST["delete_type"] === "song") {
                $songId = $_POST["song_id"];
                $result = $songsController->deleteSong($songId);
                if ($result) {
                    $message = "Canzone eliminata con successo!";
                } else {
                    $message = "Errore nell'eliminazione della canzone.";
                }
            } elseif ($_POST["delete_type"] === "artist") {
                $artistId = $_POST["artist_id"];
                $result = $artistsController->deleteArtist($artistId);
                if ($result) {
                    $message = "Artista eliminato con successo!";
                } else {
                    $message = "Errore nell'eliminazione dell'artista.";
                }
            }
            break;
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    if ($action === "edit_song") {
        $songId = $_GET["id"];
        $songToEdit = json_decode($songsController->getSongById($songId), true);
        if ($songToEdit) {
            // Fetch current artists of the song
            $songArtists = $interpretaController->getArtistiByCanzoneId(
                $songId
            );
            if (
                isset($songArtists["artisti"]) &&
                is_array($songArtists["artisti"])
            ) {
                $songArtistsIds = array_column($songArtists["artisti"], "id");
            } else {
                $songArtistsIds = []; // Ensure it's an empty array if no artists
            }
        } else {
            $message = "Canzone non trovata.";
        }
    }
}

// Recupero dati
$songs = json_decode($songsController->getAllSongs(), true);
$artists = json_decode($artistsController->getAllArtists(), true);
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>🎵 JukeBox Management</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<div id="webgl-container">
    <canvas id="bg-shader"></canvas>
</div>

<div class="container">
    <?php if ($message): ?>
        <div class="message">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    <div class="section">
        <h2>Aggiungi Canzone</h2>
        <form method="POST" action="?action=add_song">
            <input type="text" name="titolo" placeholder="Titolo" required>
            <input type="number" name="durata" placeholder="Durata (secondi)" required>
            <input type="number" name="anno" placeholder="Anno" required>
            <input type="text" name="genere" placeholder="Genere" required>
            <select name="cantanti[]" multiple required>
                <option value="" disabled selected>Seleziona Artisti</option>
                <?php if ($artists):
                    foreach ($artists as $artist): ?>
                        <option value="<?php echo htmlspecialchars(
                            $artist["id"]
                        ); ?>">
                            <?php echo htmlspecialchars(
                                $artist["nome"] . " " . $artist["cognome"]
                            ); ?>
                        </option>
                    <?php endforeach;
                endif; ?>
            </select>
            <button type="submit">Aggiungi Canzone</button>
        </form>
    </div>
    <div class="section">
        <h2>Canzoni</h2>
        <table>
            <thead>
            <tr>
                <th>Titolo</th>
                <th>Autore</th>
                <th>Anno</th>
                <th>Azioni</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($songs):
                foreach ($songs as $song):

                    // Fetch artists for each song
                    $songArtists = json_decode(
                        $interpretaController->getArtistiByCanzoneId(
                            $song["id"]
                        ),
                        true
                    );
                    if (
                        isset($songArtists["artisti"]) &&
                        is_array($songArtists["artisti"])
                    ) {
                        $artistNames = array_map(function ($artist) {
                            return htmlspecialchars(
                                $artist["nome"] . " " . $artist["cognome"]
                            );
                        }, $songArtists["artisti"]);
                        $artistList = implode(", ", $artistNames);
                    } else {
                        $artistList = "Nessun Artista";
                    }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars(
                            $song["titolo"]
                        ); ?></td>
                        <td><?php echo htmlspecialchars(
                            $song["autore"]
                        ); ?></td>
                        <td><?php echo htmlspecialchars($song["anno"]); ?></td>
                        <td><?php echo $artistList; ?></td>
                        <td>
                            <a href="?action=edit_song&id=<?php echo htmlspecialchars(
                                $song["id"]
                            ); ?>">Modifica</a>
                            <form method='POST' action='?action=delete' style='display:inline;'>
                                <input type='hidden' name='delete_type' value='song'>
                                <input type='hidden' name='song_id' value='<?php echo htmlspecialchars(
                                    $song["id"]
                                ); ?>'>
                                <button type='submit' onclick="return confirm('Sei sicuro di voler eliminare questa canzone?')">Elimina</button>
                            </form>
                        </td>
                    </tr>
                <?php
                endforeach;
            endif; ?>
            </tbody>
        </table>
    </div>
    <div class="section">
        <h2>Aggiungi Artista</h2>
        <form method="POST" action="?action=add_artist">
            <input type="text" name="nome" placeholder="Nome" required>
            <input type="text" name="cognome" placeholder="Cognome" required>
            <input type="date" name="data_nascita" placeholder="Data di Nascita" required>
            <input type="text" name="nazionalità" placeholder="Nazionalità" required>
            <button type="submit">Aggiungi Artista</button>
        </form>
    </div>
    <div class="section">
        <h2>Artisti</h2>
        <table>
            <thead>
            <tr>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Nazionalità</th>
                <th>Azioni</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($artists):
                foreach ($artists as $artist): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(
                            $artist["nome"]
                        ); ?></td>
                        <td><?php echo htmlspecialchars(
                            $artist["cognome"]
                        ); ?></td>
                        <td><?php echo htmlspecialchars(
                            $artist["nazionalità"]
                        ); ?></td>
                        <td>
                            <form method='POST' action='?action=delete' style='display:inline;'>
                                <input type='hidden' name='delete_type' value='artist'>
                                <input type='hidden' name='artist_id' value='<?php echo htmlspecialchars(
                                    $artist["id"]
                                ); ?>'>
                                <button type='submit' onclick="return confirm('Sei sicuro di voler eliminare questo artista?')">Elimina</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach;
            endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (
        isset($_GET["action"]) &&
        $_GET["action"] == "edit_song" &&
        isset($songToEdit)
    ): ?>
        <div class="section">
            <h2>Modifica Canzone</h2>
            <form method="POST" action="?action=update_song&id=<?php echo htmlspecialchars(
                $_GET["id"]
            ); ?>">
                <input type="text" name="titolo" value="<?php echo htmlspecialchars(
                    $songToEdit["titolo"]
                ); ?>" placeholder="Titolo" required>
                <input type="number" name="durata" value="<?php echo htmlspecialchars(
                    $songToEdit["durata"]
                ); ?>" placeholder="Durata (secondi)" required>
                <input type="number" name="anno" value="<?php echo htmlspecialchars(
                    $songToEdit["anno"]
                ); ?>" placeholder="Anno" required>
                <input type="text" name="genere" value="<?php echo htmlspecialchars(
                    $songToEdit["genere"]
                ); ?>" placeholder="Genere" required>
                <select name="cantanti[]" multiple required>
                    <option value="" disabled selected>Seleziona Artisti</option>
                    <?php if ($artists):
                        foreach ($artists as $artist):
                            $selected = in_array($artist["id"], $songArtistsIds)
                                ? "selected"
                                : ""; ?>
                            <option value="<?php echo htmlspecialchars(
                                $artist["id"]
                            ); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars(
                                    $artist["nome"] . " " . $artist["cognome"]
                                ); ?>
                            </option>
                        <?php
                        endforeach;
                    endif; ?>
                </select>
                <button type="submit">Aggiorna Canzone</button>
            </form>
        </div>
    <?php endif; ?>
    <div class="section">
        <h2>Gestisci Interpretazioni</h2>
        <form method="POST" action="?action=add_interpretation">
            <select name="song_id" required>
                <option value="" disabled selected>Seleziona Canzone</option>
                <?php if ($songs): ?>
                    <?php foreach ($songs as $song): ?>
                        <option value="<?php echo htmlspecialchars(
                            $song["id"]
                        ); ?>"><?php echo htmlspecialchars(
    $song["titolo"]
); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <select name="artist_id[]" multiple required>
                <option value="" disabled selected>Seleziona Artisti</option>
                <?php if ($artists): ?>
                    <?php foreach ($artists as $artist): ?>
                        <option value="<?php echo htmlspecialchars(
                            $artist["id"]
                        ); ?>"><?php echo htmlspecialchars(
    $artist["nome"] . " " . $artist["cognome"]
); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <button type="submit">Aggiungi Interpretazioni</button>
        </form>
    </div>
</div>

<script>
    window.addEventListener('load', function() {
        const canvas = document.getElementById('bg-shader');
        const gl = canvas.getContext('webgl');
        if (!gl) {
            console.error("WebGL not supported!");
            return;
        }

        // Resize canvas to fit window
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            gl.viewport(0, 0, canvas.width, canvas.height);
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        // Vertex shader source
        const vertexShaderSource = `
            attribute vec4 a_position;
            void main() {
                gl_Position = a_position;
            }
        `;

        // Fragment shader source (your provided code)
        const fragmentShaderSource = `
            #ifdef GL_ES
            precision mediump float;
            #endif

            uniform vec3      iResolution;
            uniform float     iTime;
            uniform float     iChannelTime[4];
            uniform vec4      iMouse;
            uniform vec4      iDate;
            uniform float     iSampleRate;
            uniform vec3      iChannelResolution[4];
            uniform int       iFrame;
            uniform float     iTimeDelta;
            uniform float     iTimeRate;

            // Original by localthunk (https://www.playbalatro.com)

            // Configuration (modify these values to change the effect)
            #define SPIN_ROTATION -1.0
            #define SPIN_SPEED 20.0
            #define OFFSET vec2(0.0)
            #define COLOUR_1 vec4(0.871, 0.267, 0.231, 1.0)
            #define COLOUR_2 vec4(0.0, 0.42, 0.706, 1.0)
            #define COLOUR_3 vec4(0.086, 0.137, 0.145, 1.0)
            #define CONTRAST 3.5
            #define LIGTHING 0.4
            #define SPIN_AMOUNT 0.25
            #define PIXEL_FILTER 745.0
            #define SPIN_EASE 1.0
            #define PI 3.14159265359
            #define IS_ROTATE true

            vec4 effect(vec2 screenSize, vec2 screen_coords) {
                float pixel_size = length(screenSize.xy) / PIXEL_FILTER;
                vec2 uv = (floor(screen_coords.xy*(1./pixel_size))*pixel_size - 0.5*screenSize.xy)/length(screenSize.xy) - OFFSET;
                float uv_len = length(uv);

                float speed = (SPIN_ROTATION*SPIN_EASE*0.2);
                if(IS_ROTATE){
                   speed = iTime * speed;
                }
                speed += 302.2;
                float new_pixel_angle = atan(uv.y, uv.x) + speed - SPIN_EASE*20.*(1.*SPIN_AMOUNT*uv_len + (1. - 1.*SPIN_AMOUNT));
                vec2 mid = (screenSize.xy/length(screenSize.xy))/2.;
                uv = (vec2((uv_len * cos(new_pixel_angle) + mid.x), (uv_len * sin(new_pixel_angle) + mid.y)) - mid);

                uv *= 30.;
                speed = iTime*(SPIN_SPEED);
                vec2 uv2 = vec2(uv.x+uv.y);

                for(int i=0; i < 5; i++) {
                    uv2 += sin(max(uv.x, uv.y)) + uv;
                    uv  += 0.5*vec2(cos(5.1123314 + 0.353*uv2.y + speed*0.131121),sin(uv2.x - uv2.y));
                    uv  -= 1.0*cos(uv.x + uv.y) - 1.0*sin(uv.x*0.711 - uv2.y);
                }

                float contrast_mod = (0.25*CONTRAST + 0.5*SPIN_AMOUNT + 1.2);
                float paint_res = min(2., max(0.,length(uv)*(0.035)*contrast_mod));
                float c1p = max(0.,1. - contrast_mod*abs(1.-paint_res));
                float c2p = max(0.,1. - contrast_mod*abs(paint_res));
                float c3p = 1. - min(1., c1p + c2p);
                float light = (LIGTHING - 0.2)*max(c1p*5. - 4., 0.) + LIGTHING*max(c2p*5. - 4., 0.);
                return (0.3/CONTRAST)*COLOUR_1 + (1. - 0.3/CONTRAST)*(COLOUR_1*c1p + COLOUR_2*c2p + vec4(c3p*COLOUR_3.rgb, COLOUR_1.a)) + light;
            }

            void main() {
                vec2 uv = gl_FragCoord.xy/iResolution.xy;
                gl_FragColor = effect(iResolution.xy, uv * iResolution.xy);
            }
        `;

        // Create and compile shaders
        function createShader(gl, type, source) {
            const shader = gl.createShader(type);
            gl.shaderSource(shader, source);
            gl.compileShader(shader);
            if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
                console.error("Shader compile error: " + gl.getShaderInfoLog(shader));
                gl.deleteShader(shader);
                return null;
            }
            return shader;
        }

        const vertexShader = createShader(gl, gl.VERTEX_SHADER, vertexShaderSource);
        const fragmentShader = createShader(gl, gl.FRAGMENT_SHADER, fragmentShaderSource);

        if (!vertexShader || !fragmentShader) {
            return;
        }

        // Link shaders to program
        const program = gl.createProgram();
        gl.attachShader(program, vertexShader);
        gl.attachShader(program, fragmentShader);
        gl.linkProgram(program);
        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
            console.error("Shader link error: " + gl.getProgramInfoLog(program));
            gl.deleteProgram(program);
            return;
        }
        gl.useProgram(program);

        // Set up vertex data
        const positionBuffer = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
        const positions = [-1, -1, 1, -1, -1, 1, 1, 1];
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(positions), gl.STATIC_DRAW);
        const positionAttributeLocation = gl.getAttribLocation(program, 'a_position');
        gl.enableVertexAttribArray(positionAttributeLocation);
        gl.vertexAttribPointer(positionAttributeLocation, 2, gl.FLOAT, false, 0, 0);

        // Get uniform locations
        const iResolutionLocation = gl.getUniformLocation(program, 'iResolution');
        const iTimeLocation = gl.getUniformLocation(program, 'iTime');
        const iChannelTimeLocation = gl.getUniformLocation(program, 'iChannelTime');
        const iMouseLocation = gl.getUniformLocation(program, 'iMouse');
        const iDateLocation = gl.getUniformLocation(program, 'iDate');
        const iSampleRateLocation = gl.getUniformLocation(program, 'iSampleRate');
        const iChannelResolutionLocation = gl.getUniformLocation(program, 'iChannelResolution');
        const iFrameLocation = gl.getUniformLocation(program, 'iFrame');
        const iTimeDeltaLocation = gl.getUniformLocation(program, 'iTimeDelta');
        const iFrameRateLocation = gl.getUniformLocation(program, 'iFrameRate');

        // Render loop
        function render(time) {
            // Set uniform values
            gl.uniform3f(iResolutionLocation, canvas.width, canvas.height, 1.0); // iResolution
            gl.uniform1f(iTimeLocation, time / 1000.0); // iTime

            // Initialize other uniforms (if you need them)
            gl.uniform1fv(iChannelTimeLocation, [0.0, 0.0, 0.0, 0.0]);
            gl.uniform4f(iMouseLocation, 0.0, 0.0, 0.0, 0.0);
            gl.uniform4f(iDateLocation, 0.0, 0.0, 0.0, 0.0);
            gl.uniform1f(iSampleRateLocation, 44100.0);
            gl.uniform3fv(iChannelResolutionLocation, [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0]);
            gl.uniform1i(iFrameLocation, 0);
            gl.uniform1f(iTimeDeltaLocation, 0.0);
            gl.uniform1f(iFrameRateLocation, 60.0);

            gl.clear(gl.COLOR_BUFFER_BIT);
            gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
            requestAnimationFrame(render);
        }

        requestAnimationFrame(render);
    });
</script>

</body>

</html>
