<?php
// Configurazione e inclusione delle API
include_once "conn/config.php";
include_once "conn/controllers/SongsController.php";
include_once "conn/controllers/ArtistsController.php";

$songsController = new SongsController($conn);
$artistsController = new ArtistsController($conn);

// Gestione azioni
$action = $_GET["action"] ?? "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    switch ($action) {
        case "add_song":
            $result = $songsController->createSong($_POST);
            $message = $result
                ? "Canzone aggiunta con successo!"
                : "Errore nell'aggiunta della canzone.";
            break;

        case "add_artist":
            $result = $artistsController->createArtist($_POST);
            $message = $result
                ? "Artista aggiunto con successo!"
                : "Errore nell'aggiunta dell'artista.";
            break;
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
        <style>
            /* Retro Neocities-inspired styling */
            body {
                background-color: #000000;
                color: #00FF00;
                font-family: 'Courier New', monospace;
                line-height: 1.6;
                margin: 0;
                padding: 20px;
                background-image:
                    linear-gradient(rgba(0,255,0,0.1) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(0,255,0,0.1) 1px, transparent 1px);
                background-size: 20px 20px;
            }

            .glitch {
                position: relative;
                text-transform: uppercase;
                font-size: 3rem;
                animation: glitch-skew 1s infinite linear alternate-reverse;
            }

            .glitch::before {
                content: attr(data-text);
                position: absolute;
                top: 0;
                left: -2px;
                text-shadow: -2px 0 red;
                clip: rect(44px, 450px, 56px, 0);
                animation: glitch-anim 5s infinite linear alternate-reverse;
            }

            .glitch::after {
                content: attr(data-text);
                position: absolute;
                top: 0;
                left: 2px;
                text-shadow: -2px 0 blue;
                clip: rect(44px, 450px, 56px, 0);
                animation: glitch-anim2 5s infinite linear alternate-reverse;
            }

            @keyframes glitch-anim {
                0% { clip: rect(61px, 9999px, 52px, 0); }
                5% { clip: rect(33px, 9999px, 144px, 0); }
                10% { clip: rect(121px, 9999px, 48px, 0); }
                15% { clip: rect(81px, 9999px, 137px, 0); }
                20% { clip: rect(138px, 9999px, 103px, 0); }
                25% { clip: rect(40px, 9999px, 66px, 0); }
            }

            @keyframes glitch-anim2 {
                0% { clip: rect(29px, 9999px, 83px, 0); }
                5% { clip: rect(138px, 9999px, 124px, 0); }
                10% { clip: rect(44px, 9999px, 34px, 0); }
                15% { clip: rect(104px, 9999px, 133px, 0); }
                20% { clip: rect(57px, 9999px, 59px, 0); }
                25% { clip: rect(79px, 9999px, 89px, 0); }
            }

            @keyframes glitch-skew {
                0% { transform: skew(3deg); }
                10% { transform: skew(-3deg); }
                20% { transform: skew(1deg); }
                30% { transform: skew(-1deg); }
                40% { transform: skew(2deg); }
                50% { transform: skew(-2deg); }
                60% { transform: skew(3deg); }
                70% { transform: skew(-3deg); }
            }

            .container {
                display: flex;
                justify-content: space-between;
                margin-top: 20px;
            }

            .section {
                background-color: rgba(0,0,0,0.8);
                border: 2px solid #00FF00;
                padding: 20px;
                width: 45%;
                box-shadow: 0 0 10px #00FF00;
            }

            h1, h2, h3 {
                color: #00FF00;
                text-transform: uppercase;
                letter-spacing: 2px;
            }

            table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                margin-top: 20px;
            }

            th, td {
                border: 1px solid #00FF00;
                padding: 10px;
                text-align: left;
                color: #00FF00;
            }

            th {
                background-color: rgba(0,255,0,0.2);
            }

            form {
                display: flex;
                flex-direction: column;
            }

            input, button {
                background-color: black;
                color: #00FF00;
                border: 1px solid #00FF00;
                padding: 10px;
                margin: 5px 0;
                font-family: 'Courier New', monospace;
            }

            button {
                cursor: pointer;
                transition: all 0.3s ease;
            }

            button:hover {
                background-color: #00FF00;
                color: black;
            }

            .message {
                background-color: rgba(0,255,0,0.2);
                color: #00FF00;
                padding: 15px;
                margin-bottom: 20px;
                border: 1px solid #00FF00;
            }

            /* Scrollbar styling */
            ::-webkit-scrollbar {
                width: 10px;
            }

            ::-webkit-scrollbar-track {
                background: black;
            }

            ::-webkit-scrollbar-thumb {
                background: #00FF00;
            }
        </style>
    </head>
    <body>
        <h1 class="glitch" data-text="JukeBox Management">JukeBox Management</h1>

        <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

    <div class="container">
        <div class="section">
            <h2>Aggiungi Canzone</h2>
            <form method="POST" action="?action=add_song">
                <input type="text" name="titolo" placeholder="Titolo" required>
                <input type="number" name="durata" placeholder="Durata (secondi)" required>
                <input type="number" name="anno" placeholder="Anno" required>
                <input type="text" name="genere" placeholder="Genere" required>
                <input type="text" name="autore" placeholder="Autore" required>
                <button type="submit">Aggiungi Canzone</button>
            </form>

            <h3>Canzoni</h3>
            <table>
                <tr>
                    <th>Titolo</th>
                    <th>Autore</th>
                    <th>Anno</th>
                </tr>
                <?php foreach ($songs as $song): ?>
                <tr>
                    <td><?php echo htmlspecialchars($song["titolo"]); ?></td>
                    <td><?php echo htmlspecialchars($song["autore"]); ?></td>
                    <td><?php echo htmlspecialchars($song["anno"]); ?></td>
                </tr>
                <?php endforeach; ?>
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

            <h3>Artisti</h3>
            <table>
                <tr>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Nazionalità</th>
                </tr>
                <?php foreach ($artists as $artist): ?>
                <tr>
                    <td><?php echo htmlspecialchars($artist["nome"]); ?></td>
                    <td><?php echo htmlspecialchars($artist["cognome"]); ?></td>
                    <td><?php echo htmlspecialchars(
                        $artist["nazionalità"]
                    ); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
