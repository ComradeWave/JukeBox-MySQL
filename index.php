<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Configurazione e inclusione delle API
include_once "conn/config.php";
include_once "conn/models/SongsController.php";
include_once "conn/models/ArtistController.php";
include_once "conn/models/InterpretaController.php";

$conn = createConnection();

// --- Dependency Injection ---
$songsController = new SongsController($conn);
$artistsController = new ArtistsController($conn);
$interpretaController = new InterpretaController($conn);

// --- Initialization ---
$action = $_GET["action"] ?? "";
$message = "";
$songToEdit = null;
$songArtistsIds = [];



// --- POST Request Handling ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"] ?? $action;

    switch ($action) {
        case "add_song":
            // Removed 'autore' check here, set default below
            if (
                !empty($_POST["titolo"]) &&
                isset($_POST["durata"]) &&
                isset($_POST["anno"]) &&
                !empty($_POST["genere"]) &&
                !empty($_POST["cantanti"]) &&
                is_array($_POST["cantanti"])
            ) {
                $songData = [
                    "titolo" => $_POST["titolo"],
                    "durata" => $_POST["durata"],
                    "anno" => $_POST["anno"],
                    "genere" => $_POST["genere"],
                    // Set 'autore' to empty string or NULL if removed from form
                    // Ensure DB column `autore` in `Canzone` allows NULL or has a default
                    "autore" => "", // Or potentially null if column allows
                ];

                $songId = $songsController->createSong($songData);

                if ($songId) {
                    $interpretationErrors = [];
                    foreach ($_POST["cantanti"] as $cantanteId) {
                        $result = $interpretaController->addInterpretazione(
                            $songId,
                            (int) $cantanteId
                        );
                        if (!$result["success"]) {
                            $interpretationErrors[] =
                                "Artista ID {$cantanteId}: " .
                                ($result["message"] ?? "Errore sconosciuto");
                        }
                    }
                    if (empty($interpretationErrors)) {
                        $message = "Canzone aggiunta con successo!";
                    } else {
                        $message =
                            "Canzone aggiunta, ma con errori nelle interpretazioni: " .
                            implode(", ", $interpretationErrors);
                    }
                } else {
                    $message =
                        "Errore nell'aggiunta della canzone (potrebbe esistere già).";
                }
            } else {
                $message =
                    "Errore: Compila tutti i campi obbligatori (Titolo, Durata, Anno, Genere, Interpreti) per aggiungere una canzone.";
            }
            break;

        case "update_song":
            $songId = $_GET["id"] ?? null;
            // Keep 'autore' here for update form
            if (
                $songId &&
                !empty($_POST["titolo"]) &&
                !empty($_POST["autore"]) && // Autore is still expected for update
                isset($_POST["durata"]) &&
                isset($_POST["anno"]) &&
                !empty($_POST["genere"]) &&
                isset($_POST["cantanti"]) &&
                is_array($_POST["cantanti"])
            ) {
                $songData = [
                    "titolo" => $_POST["titolo"],
                    "durata" => $_POST["durata"],
                    "anno" => $_POST["anno"],
                    "genere" => $_POST["genere"],
                    "autore" => $_POST["autore"], // Update requires autore
                    "artisti" => $_POST["cantanti"] ?? [],
                ];
                $result = $songsController->updateSong($songId, $songData);
                $message = $result
                    ? "Canzone aggiornata con successo!"
                    : "Errore nell'aggiornamento della canzone.";
            } else {
                $message =
                    "Errore: Dati mancanti o ID canzone non valido per l'aggiornamento.";
            }
            break;

        case "add_artist":
            if (
                !empty($_POST["nome"]) &&
                !empty($_POST["cognome"]) &&
                !empty($_POST["data_nascita"]) &&
                !empty($_POST["nazionalità"])
            ) {
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
            } else {
                $message =
                    "Errore: Compila tutti i campi obbligatori per aggiungere un artista.";
            }
            break;

        case "add_interpretation":
            if (
                !empty($_POST["song_id"]) &&
                !empty($_POST["artist_id"]) &&
                is_array($_POST["artist_id"])
            ) {
                $songId = (int) $_POST["song_id"];
                $artistIds = $_POST["artist_id"];
                $successCount = 0;
                $errorMessages = [];
                foreach ($artistIds as $artistId) {
                    $result = $interpretaController->addInterpretazione(
                        $songId,
                        (int) $artistId
                    );
                    if ($result["success"]) {
                        $successCount++;
                    } else {
                        $errorMessages[] =
                            "Artista ID {$artistId}: " .
                            ($result["message"] ?? "Errore sconosciuto");
                    }
                }
                // Simplified messages
                if ($successCount > 0) {
                    $message = "{$successCount} interpretazioni aggiunte/aggiornate.";
                }
                if (!empty($errorMessages)) {
                    $message .= " Errori: " . implode(", ", $errorMessages);
                }
                if ($successCount == 0 && empty($errorMessages)) {
                    $message =
                        "Nessuna nuova interpretazione aggiunta (potrebbero esistere già).";
                }
            } else {
                $message = "Errore: Seleziona una canzone e almeno un artista.";
            }
            break;

        case "delete":
            if (!empty($_POST["delete_type"])) {
                if (
                    $_POST["delete_type"] === "song" &&
                    !empty($_POST["song_id"])
                ) {
                    $songId = (int) $_POST["song_id"];
                    $result = $songsController->deleteSong($songId);
                    $message = $result
                        ? "Canzone eliminata con successo!"
                        : "Errore nell'eliminazione della canzone.";
                } elseif (
                    $_POST["delete_type"] === "artist" &&
                    !empty($_POST["artist_id"])
                ) {
                    $artistId = (int) $_POST["artist_id"];
                    // !!! IMPORTANT: Ensure ArtistController::deleteArtist handles removing interpretations
                    // If not, uncomment and adapt the logic in ArtistController.php
                    $result = $artistsController->deleteArtist($artistId);
                    $message = $result
                        ? "Artista eliminato con successo!"
                        : "Errore nell'eliminazione dell'artista.";
                } else {
                    $message =
                        "Errore: Tipo o ID non valido per l'eliminazione.";
                }
            } else {
                $message = "Errore: Tipo di eliminazione non specificato.";
            }
            break;

        case "delete_interpretation": // <-- Add this new case
            if (!empty($_POST["song_id"]) && !empty($_POST["artist_id"])) {
                $songId = (int) $_POST["song_id"];
                $artistId = (int) $_POST["artist_id"];

                // Use the existing controller method
                $result = $interpretaController->removeInterpretazione(
                    $songId,
                    $artistId
                );

                if ($result["success"]) {
                    $message = "Interpretazione rimossa con successo!";
                } else {
                    $message =
                        "Errore nella rimozione dell'interpretazione: " .
                        ($result["message"] ?? "Dettagli non disponibili.");
                }
            } else {
                $message =
                    "Errore: ID canzone o artista mancante per rimuovere l'interpretazione.";
            }
            break; // <-- Important: Add break
    }
}
// --- GET Request Handling ---
elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET["message"])) {
        $message = urldecode($_GET["message"]);
    }

    if ($action === "edit_song" && isset($_GET["id"])) {
        $songId = (int) $_GET["id"];
        $songDataJson = $songsController->getSongById($songId);
        $songToEdit = json_decode($songDataJson, true);
        if ($songToEdit) {
            $songArtistsResult = $interpretaController->getArtistiByCanzoneId(
                $songId
            );
            if (
                isset($songArtistsResult["artisti"]) &&
                is_array($songArtistsResult["artisti"])
            ) {
                $songArtistsIds = array_column(
                    $songArtistsResult["artisti"],
                    "id"
                );
            }
        } else {
            $message = "Errore: Canzone non trovata per la modifica.";
            $action = "";
        }
    }
}

// --- Data Fetching for Display (with Search) ---
$searchTerm = $_GET["search"] ?? "";
$songs = [];
$artists = [];

if (!empty($searchTerm)) {
    // --- Perform Search ---
    $likeTerm = "%{$searchTerm}%";

    // Search Songs (adjust fields as needed)
    $sqlSongs =
        "SELECT * FROM Canzone WHERE titolo LIKE ? OR autore LIKE ? OR genere LIKE ? OR anno LIKE ?";
    $stmtSongs = mysqli_prepare($conn, $sqlSongs);
    if ($stmtSongs) {
        mysqli_stmt_bind_param(
            $stmtSongs,
            "ssss",
            $likeTerm,
            $likeTerm,
            $likeTerm,
            $likeTerm
        );
        mysqli_stmt_execute($stmtSongs);
        $resultSongs = mysqli_stmt_get_result($stmtSongs);
        while ($row = mysqli_fetch_assoc($resultSongs)) {
            $songs[] = $row;
        }
        mysqli_stmt_close($stmtSongs);
    } else {
        error_log(
            "Error preparing song search statement: " . mysqli_error($conn)
        );
        $message .= " Errore nella ricerca canzoni.";
    }

    // Search Artists (using existing controller method for consistency)
    // We assume $artistsController->searchArtists returns JSON
    $artistsDataJson = $artistsController->searchArtists($searchTerm);
    $artists = json_decode($artistsDataJson, true) ?: [];
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log(
            "Error decoding artist search results: " . json_last_error_msg()
        );
        $message .= " Errore nella decodifica risultati artisti.";
        $artists = []; // Ensure it's an array
    }

    if (empty($songs) && empty($artists)) {
        $message =
            "Nessun risultato trovato per '" .
            htmlspecialchars($searchTerm) .
            "'.";
    }
} else {
    // --- Fetch All if no search term ---
    $songs = json_decode($songsController->getAllSongs(), true) ?: [];
    $artists = json_decode($artistsController->getAllArtists(), true) ?: [];
}

// Pass data to JavaScript
$artistsJson = json_encode($artists);
$songsJson = json_encode($songs);

// Needed for song deletion search
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>🎵 JukeBox Management</title>
    <link rel="stylesheet" href="style.css">
    </head>

<body>
    <div id="webgl-container"><canvas id="bg-shader"></canvas></div>

    <div class="container">
        <h1>JukeBox Management</h1> <?php if ($message): ?>
        <div class="message" id="feedback-message"><?php echo htmlspecialchars(
            $message
        ); ?></div>
        <?php endif; ?>

        <div class="section search-section">
             <h2>Ricerca Globale</h2>
             <form method="GET" action="index.php" id="search-form">
                <input type="text" id="search-input" name="search" placeholder="Cerca per titolo, artista, genere, anno..." value="<?php echo htmlspecialchars(
                    $searchTerm
                ); ?>" >
                <button type="submit">Cerca</button>
                <?php if (!empty($searchTerm)): ?>
                    <a href="index.php" class="button-link-inline" style="margin-left: 10px;">Mostra Tutto</a>
                <?php endif; ?>
             </form>
        </div>

        <?php
// Existing conditional display for Add vs Edit Song form
?>
        <?php if ($action == "edit_song" && isset($songToEdit)): ?>
        <div class="section">
            <h2>Modifica Canzone</h2>
            <form method="POST" action="?action=update_song&id=<?php echo htmlspecialchars(
                $songToEdit["id"]
            ); ?>">
                 <input type="hidden" name="action" value="update_song">
                 <label for="edit-titolo">Titolo:</label>
                 <input id="edit-titolo" type="text" name="titolo" value="<?php echo htmlspecialchars(
                     $songToEdit["titolo"]
                 ); ?>" required>
                 <label for="edit-autore">Autore:</label>
                 <input id="edit-autore" type="text" name="autore" value="<?php echo htmlspecialchars(
                     $songToEdit["autore"]
                 ); ?>" required> <label for="edit-durata">Durata (sec):</label>
                 <input id="edit-durata" type="number" name="durata" value="<?php echo htmlspecialchars(
                     $songToEdit["durata"]
                 ); ?>" required>
                 <label for="edit-anno">Anno:</label>
                 <input id="edit-anno" type="number" name="anno" value="<?php echo htmlspecialchars(
                     $songToEdit["anno"]
                 ); ?>" required>
                 <label for="edit-genere">Genere:</label>
                 <input id="edit-genere" type="text" name="genere" value="<?php echo htmlspecialchars(
                     $songToEdit["genere"]
                 ); ?>" required>
                 <label for="edit-cantanti">Artisti (Interpreti):</label>
                 <select id="edit-cantanti" name="cantanti[]" multiple required size="5">
                     <option value="" disabled>Seleziona Artisti</option>
                     <?php if ($artists):
                         foreach ($artists as $artist):
                             $selected = in_array(
                                 $artist["id"],
                                 $songArtistsIds
                             )
                                 ? "selected"
                                 : ""; ?>
                         <option value="<?php echo htmlspecialchars(
                             $artist["id"]
                         ); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars(
    $artist["nome"] . " " . $artist["cognome"]
); ?></option>
                     <?php
                         endforeach;
                     else:
                          ?> <option value="" disabled>Nessun artista</option> <?php
                     endif; ?>
                 </select>
                 <button type="submit">Aggiorna Canzone</button>
                 <a href="index.php" class="button-link">Annulla Modifica</a>
             </form>
        </div>
        <?php else: ?>
        <div class="section">
            <h2>Aggiungi Canzone</h2>
            <form method="POST" action="?action=add_song">
                 <input type="hidden" name="action" value="add_song">
                 <label for="add-titolo">Titolo:</label>
                 <input id="add-titolo" type="text" name="titolo" placeholder="Titolo" required>
                 <label for="add-durata">Durata (sec):</label>
                 <input id="add-durata" type="number" name="durata" placeholder="Es. 180" required>
                 <label for="add-anno">Anno:</label>
                 <input id="add-anno" type="number" name="anno" placeholder="Es. 1999" required>
                 <label for="add-genere">Genere:</label>
                 <input id="add-genere" type="text" name="genere" placeholder="Genere" required>
                 <label for="add-cantanti">Artisti (Interpreti):</label>
                 <select id="add-cantanti" name="cantanti[]" multiple size="5">
                     <option value="" disabled selected>Seleziona Artisti</option>
                     <?php if ($artists):
                         foreach ($artists as $artist): ?>
                         <option value="<?php echo htmlspecialchars(
                             $artist["id"]
                         ); ?>"><?php echo htmlspecialchars(
    $artist["nome"] . " " . $artist["cognome"]
); ?></option>
                     <?php endforeach;
                     else:
                          ?> <option value="" disabled>Nessun artista</option> <?php
                     endif; ?>
                 </select>
                 <button type="submit">Aggiungi Canzone</button>
             </form>
        </div>
        <?php endif; ?>

        <div class="section collapsible-section collapsed" id="songs-section">
            <h2 title="Clicca per espandere/collassare">Canzoni</h2>
            <div class="collapsible-content">
                <table id="songs-table">
                    <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Anno</th>
                        <th>Interpreti</th>
                        <th>Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($songs)):
                        foreach ($songs as $song):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars(
                                        $song["titolo"]
                                    ); ?></td>
                                <td><?php echo htmlspecialchars(
                                        $song["anno"]
                                    ); ?></td>
                                <td>
                                    <?php
                                    // --- Fetch Interpreters for THIS song ---
                                    $interpretiResult = $interpretaController->getArtistiByCanzoneId(
                                        $song["id"]
                                    );
                                    if (
                                        $interpretiResult["success"] &&
                                        !empty($interpretiResult["artisti"])
                                    ) {
                                        echo "<ul class='interpreti-list'>"; // Use a list for better structure
                                        foreach (
                                            $interpretiResult["artisti"]
                                            as $artista
                                        ) {
                                            echo "<li>";
                                            echo htmlspecialchars(
                                                $artista["nome"] .
                                                " " .
                                                $artista["cognome"]
                                            );
                                            // --- Add small delete form for this interpretation ---
                                            echo "<form method='POST' action='index.php' class='delete-interpretation-form'>";
                                            echo "<input type='hidden' name='action' value='delete_interpretation'>";
                                            echo "<input type='hidden' name='song_id' value='" .
                                                htmlspecialchars($song["id"]) .
                                                "'>";
                                            echo "<input type='hidden' name='artist_id' value='" .
                                                htmlspecialchars($artista["id"]) .
                                                "'>";
                                            echo "<button type='submit' class='button-delete-interpretation' title='Rimuovi questa interpretazione' onclick=\"return confirm('Rimuovere l\'interpretazione di " .
                                                htmlspecialchars(
                                                    addslashes(
                                                        $artista["nome"] .
                                                        " " .
                                                        $artista["cognome"]
                                                    )
                                                ) .
                                                " per la canzone " .
                                                htmlspecialchars(
                                                    addslashes($song["titolo"])
                                                ) .
                                                "?')\">×</button>"; // Use '×' symbol
                                            echo "</form>";
                                            // --- End delete form ---
                                            echo "</li>";
                                        }
                                        echo "</ul>";
                                    } elseif (!$interpretiResult["success"]) {
                                        echo "Errore DB";
                                    } else {
                                        echo "Nessuno";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="?action=edit_song&id=<?php echo htmlspecialchars(
                                        $song["id"]
                                    ); ?>" class="button-link-inline">Modifica</a>
                                    <form method='POST' action='index.php' style='display:inline;'>
                                        <input type='hidden' name='action' value='delete'><input type='hidden' name='delete_type' value='song'>
                                        <input type='hidden' name='song_id' value='<?php echo htmlspecialchars(
                                            $song["id"]
                                        ); ?>'>
                                        <button type='submit' class="button-delete" onclick="return confirm('Eliminare la canzone <?php echo htmlspecialchars(
                                            addslashes($song["titolo"])
                                        ); ?>? Verranno rimosse anche TUTTE le interpretazioni.')">Elimina Canzone</button>
                                    </form>
                                </td>
                            </tr>
                        <?php
                        endforeach;
                    else:
                        ?>
                        <tr>
                            <td colspan="4">Nessuna canzone trovata.</td>
                        </tr>
                    <?php
                    endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <h2>Aggiungi Artista</h2>
            <form method="POST" action="?action=add_artist">
                <input type="hidden" name="action" value="add_artist">
                 <label for="add-nome">Nome:</label><input id="add-nome" type="text" name="nome" required>
                 <label for="add-cognome">Cognome:</label><input id="add-cognome" type="text" name="cognome" required>
                 <label for="add-data">Data di Nascita:</label><input id="add-data" type="date" name="data_nascita" required>
                 <label for="add-naz">Nazionalità:</label><input id="add-naz" type="text" name="nazionalità" required>
                 <button type="submit">Aggiungi Artista</button>
             </form>
        </div>

        <div class="section collapsible-section collapsed" id="artists-section">
             <h2 title="Clicca per espandere/collassare">Artisti</h2>
             <div class="collapsible-content">
                 <table id="artists-table">
                     <thead><tr><th>Nome</th><th>Cognome</th><th>Nazionalità</th><th>Azioni</th></tr></thead>
                     <tbody>
                         <?php if (!empty($artists)):
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
                                 <form method='POST' action='index.php' style='display:inline;'>
                                     <input type='hidden' name='action' value='delete'><input type='hidden' name='delete_type' value='artist'>
                                     <input type='hidden' name='artist_id' value='<?php echo htmlspecialchars(
                                         $artist["id"]
                                     ); ?>'>
                                     <button type='submit' class="button-delete" onclick="return confirm('Eliminare questo artista? Verranno rimosse anche le interpretazioni.')">Elimina</button>
                                 </form>
                             </td>
                         </tr>
                         <?php endforeach;
                         else:
                              ?><tr><td colspan="4">Nessun artista trovato.</td></tr><?php
                         endif; ?>
                     </tbody>
                 </table>
             </div>
        </div>

        <div class="section">
            <h2>Gestisci Interpretazioni (Associa Artisti a Canzone)</h2>
            <form method="POST" action="?action=add_interpretation">
                <input type="hidden" name="action" value="add_interpretation">
                <label for="interp-song-select">Seleziona Canzone:</label>
                <select id="interp-song-select" name="song_id" required>
                    <option value="" disabled selected>-- Seleziona una Canzone --</option>
                    <?php if (!empty($songs)):
                        foreach ($songs as $song):
                            // --- Fetch Interpreters for THIS specific song ---
                            $interpretiDropdownResult = $interpretaController->getArtistiByCanzoneId($song["id"]);
                            $interpretiListText = "Nessuno"; // Default text if no interpreters

                            if ($interpretiDropdownResult["success"] && !empty($interpretiDropdownResult["artisti"])) {
                                // Map artist names (first name + last name)
                                $interpretiNames = array_map(
                                    fn($a) => trim(htmlspecialchars($a["nome"] . " " . $a["cognome"])),
                                    $interpretiDropdownResult["artisti"]
                                );
                                // Join names with a comma
                                $interpretiListText = implode(", ", $interpretiNames);

                                // Optional: Truncate if the list is very long for the dropdown display
                                $maxLength = 50; // Adjust max characters as needed
                                if (mb_strlen($interpretiListText) > $maxLength) {
                                    $interpretiListText = mb_substr($interpretiListText, 0, $maxLength - 3) . "...";
                                }

                            } elseif (!$interpretiDropdownResult["success"]) {
                                $interpretiListText = "Errore DB"; // Indicate if fetching failed
                            }

                            // --- Construct the option text ---
                            $optionDisplayText = sprintf(
                                "%s (%s, %s)",
                                htmlspecialchars($song["titolo"]),
                                $interpretiListText, // Use the fetched and formatted interpreters list
                                htmlspecialchars($song["anno"])
                            );
                            ?>
                            <option value="<?php echo htmlspecialchars($song["id"]); ?>">
                                <?php echo $optionDisplayText; // Display the new format ?>
                            </option>
                        <?php endforeach;
                    else:
                        ?><option value="" disabled>Nessuna canzone</option><?php
                    endif; ?>
                </select>
                <label for="interp-artist-select">Seleziona Artisti da Associare:</label>
                <select id="interp-artist-select" name="artist_id[]" multiple required size="5">
                    <option value="" disabled>-- Seleziona uno o più Artisti --</option>
                    <?php if (!empty($artists)):
                        foreach ($artists as $artist): ?>
                            <option value="<?php echo htmlspecialchars($artist["id"]); ?>">
                                <?php echo htmlspecialchars($artist["nome"] . " " . $artist["cognome"]); ?>
                            </option>
                        <?php endforeach;
                    else:
                        ?><option value="" disabled>Nessun artista</option><?php
                    endif; ?>
                </select>
                <button type="submit">Aggiungi/Associa Interpretazioni</button>
            </form>
        </div>

         <div class="section">
            <h2>Cancella Artista (con ricerca)</h2>
            <form method="POST" action="index.php" id="delete-artist-form">
                <input type='hidden' name='action' value='delete'>
                <input type='hidden' name='delete_type' value='artist'>
                <input type='hidden' name='artist_id' id="delete-artist-id" value=''> <label for="delete-artist-search">Cerca e seleziona Artista da Eliminare:</label>
                <input type="text" id="delete-artist-search" placeholder="Inizia a scrivere nome o cognome..." autocomplete="off">
                <div id="delete-artist-suggestions"></div> <button type="submit" class="button-delete" id="delete-artist-submit-button" disabled>
                    Elimina Artista Selezionato
                </button>
            </form>
        </div>

        <div class="section">
            <h2>Cancella Canzone (con ricerca)</h2>
            <form method="POST" action="index.php" id="delete-song-form">
                <input type='hidden' name='action' value='delete'>
                <input type='hidden' name='delete_type' value='song'>
                <input type='hidden' name='song_id' id="delete-song-id" value=''>
                <label for="delete-song-search">Cerca e seleziona Canzone da Eliminare:</label>
                <input type="text" id="delete-song-search" placeholder="Inizia a scrivere titolo, genere o anno..." autocomplete="off">
                <div id="delete-song-suggestions"></div>
                <button type="submit" class="button-delete" id="delete-song-submit-button" disabled>
                    Elimina Canzone Selezionata
                </button>
            </form>
        </div>


    </div> <script>
        // Make artist data available to JS
        const allArtists = <?php echo $artistsJson; ?>;
        const allSongs = <?php echo $songsJson; ?>;

        // --- Debounce function ---
        function debounce(func, wait) { /* ... debounce code ... */
            let timeout; return function executedFunction(...args) { const later = () => { clearTimeout(timeout); func(...args); }; clearTimeout(timeout); timeout = setTimeout(later, wait); };
        }

        window.addEventListener('load', function() {
            // --- WebGL Background Initialization ---
            const canvas = document.getElementById('bg-shader');
            if (canvas) {
                const gl = canvas.getContext('webgl');
                if (!gl) { console.error("WebGL not supported! Hiding background."); const c = document.getElementById('webgl-container'); if (c) c.style.display = 'none'; }
                else {
                    let program, iResolutionLocation, iTimeLocation, positionAttributeLocation, positionBuffer, startTime = Date.now();
                    function resizeCanvas() { if (!gl || gl.isContextLost()) return; canvas.width = window.innerWidth; canvas.height = window.innerHeight; gl.viewport(0, 0, canvas.width, canvas.height); if (program && iResolutionLocation) { gl.useProgram(program); gl.uniform3f(iResolutionLocation, canvas.width, canvas.height, 1.0); } }
                    function createShader(gl, type, source) { const s = gl.createShader(type); gl.shaderSource(s, source); gl.compileShader(s); if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) { console.error("Shader compile error: " + gl.getShaderInfoLog(s)); gl.deleteShader(s); return null; } return s; }
                    function initWebGL() {
                        const vs = `attribute vec4 a_position; void main() { gl_Position = a_position; }`;
                        // Ensure your full, correct fragment shader code is pasted here
                        const fs = `#ifdef GL_ES\nprecision mediump float;\n#endif\nuniform vec3 iResolution; uniform float iTime;\n/* --- PASTE YOUR FULL FRAGMENT SHADER CODE HERE --- */\n#define SPIN_ROTATION -1.0\n#define SPIN_SPEED 20.0\n#define OFFSET vec2(0.0)\n#define COLOUR_1 vec4(0.871, 0.267, 0.231, 1.0)\n#define COLOUR_2 vec4(0.0, 0.42, 0.706, 1.0)\n#define COLOUR_3 vec4(0.086, 0.137, 0.145, 1.0)\n#define CONTRAST 3.5\n#define LIGTHING 0.4\n#define SPIN_AMOUNT 0.25\n#define PIXEL_FILTER 745.0\n#define SPIN_EASE 1.0\n#define PI 3.14159265359\n#define IS_ROTATE true\nvec4 effect(vec2 screenSize, vec2 screen_coords) { float pixel_size = length(screenSize.xy) / PIXEL_FILTER; vec2 uv = (floor(screen_coords.xy*(1./pixel_size))*pixel_size - 0.5*screenSize.xy)/length(screenSize.xy) - OFFSET; float uv_len = length(uv); float speed = (SPIN_ROTATION*SPIN_EASE*0.2); if(IS_ROTATE){ speed = iTime * speed; } speed += 302.2; float new_pixel_angle = atan(uv.y, uv.x) + speed - SPIN_EASE*20.*(1.*SPIN_AMOUNT*uv_len + (1. - 1.*SPIN_AMOUNT)); vec2 mid = (screenSize.xy/length(screenSize.xy))/2.; uv = (vec2((uv_len * cos(new_pixel_angle) + mid.x), (uv_len * sin(new_pixel_angle) + mid.y)) - mid); uv *= 30.; speed = iTime*(SPIN_SPEED); vec2 uv2 = vec2(uv.x+uv.y); for(int i=0; i < 5; i++) { uv2 += sin(max(uv.x, uv.y)) + uv; uv += 0.5*vec2(cos(5.1123314 + 0.353*uv2.y + speed*0.131121),sin(uv2.x - uv2.y)); uv -= 1.0*cos(uv.x + uv.y) - 1.0*sin(uv.x*0.711 - uv2.y); } float contrast_mod = (0.25*CONTRAST + 0.5*SPIN_AMOUNT + 1.2); float paint_res = min(2., max(0.,length(uv)*(0.035)*contrast_mod)); float c1p = max(0.,1. - contrast_mod*abs(1.-paint_res)); float c2p = max(0.,1. - contrast_mod*abs(paint_res)); float c3p = 1. - min(1., c1p + c2p); float light = (LIGTHING - 0.2)*max(c1p*5. - 4., 0.) + LIGTHING*max(c2p*5. - 4., 0.); return (0.3/CONTRAST)*COLOUR_1 + (1. - 0.3/CONTRAST)*(COLOUR_1*c1p + COLOUR_2*c2p + vec4(c3p*COLOUR_3.rgb, COLOUR_1.a)) + light; }\nvoid main() { vec2 uv = gl_FragCoord.xy / iResolution.xy; gl_FragColor = effect(iResolution.xy, uv * iResolution.xy); }`;
                        const v = createShader(gl, gl.VERTEX_SHADER, vs), f = createShader(gl, gl.FRAGMENT_SHADER, fs); if (!v || !f) return;
                        program = gl.createProgram(); gl.attachShader(program, v); gl.attachShader(program, f); gl.linkProgram(program); if (!gl.getProgramParameter(program, gl.LINK_STATUS)) { console.error("Link error: " + gl.getProgramInfoLog(program)); gl.deleteProgram(program); program = null; return; }
                        gl.useProgram(program); iResolutionLocation = gl.getUniformLocation(program, 'iResolution'); iTimeLocation = gl.getUniformLocation(program, 'iTime');
                        positionBuffer = gl.createBuffer(); gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer); const p = [-1,-1,1,-1,-1,1,1,1]; gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(p), gl.STATIC_DRAW);
                        positionAttributeLocation = gl.getAttribLocation(program, 'a_position'); gl.enableVertexAttribArray(positionAttributeLocation); gl.vertexAttribPointer(positionAttributeLocation, 2, gl.FLOAT, false, 0, 0);
                        resizeCanvas(); requestAnimationFrame(render);
                    }
                    function render() { if (!gl || gl.isContextLost() || !program) return; let ct = (Date.now() - startTime) / 1000.0; gl.useProgram(program); gl.uniform1f(iTimeLocation, ct); gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer); gl.vertexAttribPointer(positionAttributeLocation, 2, gl.FLOAT, false, 0, 0); gl.enableVertexAttribArray(positionAttributeLocation); gl.clear(gl.COLOR_BUFFER_BIT); gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4); requestAnimationFrame(render); }
                    const dr = debounce(resizeCanvas, 250); window.addEventListener('resize', dr);
                    initWebGL();
                }
            }

            // --- Collapsible Sections Logic ---
            document.querySelectorAll('.collapsible-section > h2').forEach(header => {
                header.addEventListener('click', () => {
                    header.parentElement.classList.toggle('collapsed');
                });
                // Optional: Start collapsed by default
                // header.parentElement.classList.add('collapsed');
            });

            // --- Song Delete Suggestion Logic ---
            const songSearchInput = document.getElementById('delete-song-search');
            const songSuggestionsDiv = document.getElementById('delete-song-suggestions');
            const hiddenSongIdInput = document.getElementById('delete-song-id');
            const deleteSongButton = document.getElementById('delete-song-submit-button');
            // Ensure allSongs is defined and is an array passed correctly from PHP
            // const allSongs = <?php echo $songsJson; ?>; // This should be defined earlier in the script block

            // Check if all necessary elements and the allSongs data exist and are valid
            if (songSearchInput && songSuggestionsDiv && hiddenSongIdInput && deleteSongButton && typeof allSongs !== 'undefined' && Array.isArray(allSongs)) {

                songSearchInput.addEventListener('input', debounce(function() {
                    const query = songSearchInput.value.toLowerCase().trim();
                    songSuggestionsDiv.innerHTML = ''; // Clear previous suggestions
                    hiddenSongIdInput.value = ''; // Clear hidden ID value
                    deleteSongButton.disabled = true; // Disable button until a selection is made

                    if (query.length < 2) { // Minimum characters to start searching
                        return;
                    }

                    // Filter the songs based on the query (checking title, genre, year)
                    const filteredSongs = allSongs.filter(song => {
                        // Ensure properties exist before accessing them
                        const title = song.titolo ? song.titolo.toLowerCase() : '';
                        const genre = song.genere ? song.genere.toLowerCase() : '';
                        const year = song.anno ? String(song.anno) : ''; // Convert year to string for searching
                        const searchText = `${title} ${genre} ${year}`;
                        return searchText.includes(query);
                    });

                    // Limit the number of suggestions displayed
                    const suggestionsToShow = filteredSongs.slice(0, 10);

                    // Create and append suggestion items
                    suggestionsToShow.forEach(song => {
                        const item = document.createElement('div');
                        item.textContent = `${song.titolo} (${song.anno}) - ${song.genere}`; // Text to display
                        item.classList.add('suggestion-item'); // Apply CSS class
                        item.dataset.id = song.id; // Store the song ID in a data attribute
                        item.style.cursor = 'pointer'; // Make it look clickable

                        // Add click event listener to each suggestion item
                        item.addEventListener('click', function() {
                            songSearchInput.value = this.textContent; // Fill the input field with the selected text
                            hiddenSongIdInput.value = this.dataset.id; // Set the hidden input's value to the song ID
                            songSuggestionsDiv.innerHTML = ''; // Clear the suggestions list
                            deleteSongButton.disabled = false; // Enable the delete button
                        });
                        songSuggestionsDiv.appendChild(item);
                    });

                }, 300)); // Debounce time in milliseconds

                // Optional: Clear suggestions if the user clicks outside the search area
                document.addEventListener('click', function(event) {
                    if (!songSearchInput.contains(event.target) && !songSuggestionsDiv.contains(event.target)) {
                        songSuggestionsDiv.innerHTML = '';
                    }
                });

                // Add confirmation dialog on form submission
                const deleteSongForm = document.getElementById('delete-song-form');
                if (deleteSongForm) {
                    deleteSongForm.addEventListener('submit', function(event) {
                        // Double-check if an ID has been selected before submitting
                        if (!hiddenSongIdInput.value) {
                            alert('Per favore, seleziona una canzone dalla lista prima di eliminare.');
                            event.preventDefault(); // Stop submission
                            return;
                        }
                        // Confirm deletion with the user
                        if (!confirm('ATTENZIONE! Sei sicuro di voler eliminare la canzone selezionata? Tutte le sue interpretazioni verranno perse.')) {
                            event.preventDefault(); // Stop submission if user cancels
                        }
                        // If confirmed, the form submits normally
                    });
                }

            } else {
                // Log an error if setup fails (useful for debugging)
                console.error("Errore: Impossibile inizializzare l'autocomplete per la cancellazione delle canzoni.");
                if (!songSearchInput) console.error("Elemento non trovato: #delete-song-search");
                if (!songSuggestionsDiv) console.error("Elemento non trovato: #delete-song-suggestions");
                if (!hiddenSongIdInput) console.error("Elemento non trovato: #delete-song-id");
                if (!deleteSongButton) console.error("Elemento non trovato: #delete-song-submit-button");
                if (typeof allSongs === 'undefined' || !Array.isArray(allSongs)) {
                    console.error("Variabile 'allSongs' non definita o non è un array:", allSongs);
                }
            }
            // --- End of Song Delete Suggestion Logic ---
             // --- Artist Delete Suggestion Logic ---
            const artistSearchInput = document.getElementById('delete-artist-search');
            const artistSuggestionsDiv = document.getElementById('delete-artist-suggestions');
            const hiddenArtistIdInput = document.getElementById('delete-artist-id');
            const deleteArtistButton = document.getElementById('delete-artist-submit-button');

            if (artistSearchInput && artistSuggestionsDiv && hiddenArtistIdInput && deleteArtistButton && allArtists) {
                 artistSearchInput.addEventListener('input', debounce(function() {
                     const query = artistSearchInput.value.toLowerCase().trim();
                     artistSuggestionsDiv.innerHTML = ''; // Clear previous suggestions
                     hiddenArtistIdInput.value = ''; // Clear hidden ID
                     deleteArtistButton.disabled = true; // Disable button

                     if (query.length < 2) return; // Start searching after 2 chars

                     const filteredArtists = allArtists.filter(artist => {
                         const fullName = `${artist.nome} ${artist.cognome}`.toLowerCase();
                         return fullName.includes(query);
                     });

                     // Limit suggestions shown
                     const suggestionsToShow = filteredArtists.slice(0, 10);

                     suggestionsToShow.forEach(artist => {
                         const item = document.createElement('div');
                         item.textContent = `${artist.nome} ${artist.cognome}`;
                         item.classList.add('suggestion-item'); // Add class for styling/selection
                         item.dataset.id = artist.id; // Store ID in data attribute
                         item.addEventListener('click', function() {
                             artistSearchInput.value = this.textContent; // Fill input with selected name
                             hiddenArtistIdInput.value = this.dataset.id; // Set the hidden ID field
                             artistSuggestionsDiv.innerHTML = ''; // Clear suggestions
                             deleteArtistButton.disabled = false; // Enable delete button
                         });
                         artistSuggestionsDiv.appendChild(item);
                     });
                 }, 300)); // Debounce suggestion input

                 // Clear suggestions if user clicks away (optional)
                 document.addEventListener('click', function(event) {
                      if (!artistSearchInput.contains(event.target) && !artistSuggestionsDiv.contains(event.target)) {
                           artistSuggestionsDiv.innerHTML = '';
                      }
                  });

                  // Add confirmation on form submit (now handled by onclick in button)
                 /* document.getElementById('delete-artist-form').addEventListener('submit', function(event){
                    if (!confirm('ATTENZIONE! Eliminare l\'artista selezionato? Tutte le sue interpretazioni verranno perse.')){
                         event.preventDefault(); // Cancel submission if user clicks Cancel
                    }
                 }); */
                 // Attach confirmation directly to button's onclick for simplicity if needed,
                 // but the form submit listener is generally better practice.
                 // Re-adding the submit listener approach for better practice:
                 const deleteArtistForm = document.getElementById('delete-artist-form');
                 if (deleteArtistForm) {
                    deleteArtistForm.addEventListener('submit', function(event) {
                        if (!confirm('ATTENZIONE! Eliminare l\'artista selezionato? Tutte le sue interpretazioni verranno perse.')) {
                            event.preventDefault(); // Stop submission if user cancels
                        }
                        // If user confirms, the form submits normally
                    });
                 }
            }


            // --- Optional: Hide Feedback Message ---
            setTimeout(() => {
                const msg = document.getElementById('feedback-message');
                if (msg) { msg.style.transition = 'opacity 1s ease-out'; msg.style.opacity = '0'; setTimeout(() => { if (msg) msg.style.display = 'none'; }, 1000); }
            }, 5000);

        });
        window.addEventListener('load', function() {
                    const canvas = document.getElementById('bg-shader');
                    if (!canvas) return; // Exit if canvas not found

                    const gl = canvas.getContext('webgl');
                    if (!gl) {
                        console.error("WebGL not supported! Hiding background.");
                        const container = document.getElementById('webgl-container');
                        if (container) container.style.display = 'none'; // Hide container if WebGL fails
                        return;
                    }

                    // --- WebGL Variables (accessible to nested functions) ---
                    let program;
                    let iResolutionLocation;
                    let iTimeLocation;
                    let positionAttributeLocation;
                    let positionBuffer;
                    let startTime = Date.now();

                    // --- Resize canvas function ---
                    function resizeCanvas() {
                        if (!gl || gl.isContextLost()) return; // Don't run if context lost

                        canvas.width = window.innerWidth;
                        canvas.height = window.innerHeight; // Use viewport height
                        gl.viewport(0, 0, canvas.width, canvas.height);

                        // Update iResolution uniform immediately after viewport change
                        if (program && iResolutionLocation) {
                             gl.useProgram(program); // Ensure program is active
                             gl.uniform3f(iResolutionLocation, canvas.width, canvas.height, 1.0);
                        }
                        // No need to explicitly call render here, requestAnimationFrame handles it
                    }

                    // --- Shader creation function ---
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

                    // --- Initialize WebGL ---
                    function initWebGL() {
                        // Vertex shader source
                        const vertexShaderSource = `attribute vec4 a_position; void main() { gl_Position = a_position; }`;

                        // Fragment shader source (Use your full shader code here)
                        const fragmentShaderSource = `
                            #ifdef GL_ES
                            precision mediump float;
                            #endif
                            uniform vec3 iResolution; uniform float iTime;
                            // --- Include your full fragment shader code here ---
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
                                vec2 uv = gl_FragCoord.xy / iResolution.xy;
                                // uv.y = 1.0 - uv.y; // Uncomment if texture is upside down
                                gl_FragColor = effect(iResolution.xy, uv * iResolution.xy);
                            }`;

                        // Create and compile shaders
                        const vertexShader = createShader(gl, gl.VERTEX_SHADER, vertexShaderSource);
                        const fragmentShader = createShader(gl, gl.FRAGMENT_SHADER, fragmentShaderSource);
                        if (!vertexShader || !fragmentShader) return; // Stop if shaders failed

                        // Link shaders to program
                        program = gl.createProgram();
                        gl.attachShader(program, vertexShader);
                        gl.attachShader(program, fragmentShader);
                        gl.linkProgram(program);
                        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
                            console.error("Shader link error: " + gl.getProgramInfoLog(program));
                            gl.deleteProgram(program);
                            program = null; // Mark program as invalid
                            return;
                        }
                        gl.useProgram(program);

                        // Get uniform locations (store them in outer scope)
                        iResolutionLocation = gl.getUniformLocation(program, 'iResolution');
                        iTimeLocation = gl.getUniformLocation(program, 'iTime');
                         // Get other uniform locations here if needed...

                        // Set up vertex data
                        positionBuffer = gl.createBuffer();
                        gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
                        const positions = [-1, -1, 1, -1, -1, 1, 1, 1];
                        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(positions), gl.STATIC_DRAW);
                        positionAttributeLocation = gl.getAttribLocation(program, 'a_position');
                        gl.enableVertexAttribArray(positionAttributeLocation);
                        gl.vertexAttribPointer(positionAttributeLocation, 2, gl.FLOAT, false, 0, 0);

                        // Initial resize to set correct size and uniforms
                        resizeCanvas();

                         // Start the render loop only if initialization succeeded
                         requestAnimationFrame(render);
                    }


                    // --- Render loop ---
                    function render() {
                        if (!gl || gl.isContextLost() || !program) {
                             console.log("WebGL context lost or program invalid. Stopping render loop.");
                             return; // Stop rendering if context is lost or program failed
                         }

                        let currentTime = (Date.now() - startTime) / 1000.0;

                        gl.useProgram(program); // Ensure program is active

                        // Set time uniform (resolution is set in resizeCanvas)
                        gl.uniform1f(iTimeLocation, currentTime);
                        // Set other uniforms here if needed...

                        // Bind vertex buffer and draw
                        gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
                        gl.vertexAttribPointer(positionAttributeLocation, 2, gl.FLOAT, false, 0, 0);
                        gl.enableVertexAttribArray(positionAttributeLocation); // May need re-enabling if state changes

                        gl.clear(gl.COLOR_BUFFER_BIT);
                        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);

                        requestAnimationFrame(render); // Continue animation loop
                    }

                    // --- Event Listeners ---

                    // Debounced resize handler
                    const debouncedResize = debounce(resizeCanvas, 250); // 250ms delay
                    window.addEventListener('resize', debouncedResize);

                    // Optional: Mutation observer for content changes affecting height
                    // If you need the canvas to resize when content *below* it changes height,
                    // you might need this, but it can be resource-intensive.
                    // Consider if the debounced window resize is sufficient.
                    /*
                    const observer = new MutationObserver(debounce(resizeCanvas, 100)); // Debounce observer calls too
                    observer.observe(document.body, {
                        childList: true, // Listen for added/removed elements
                        subtree: true,   // Observe descendants
                        attributes: true, // Observe attribute changes (like style)
                        characterData: true // Observe text changes
                    });
                    */

                    // --- Initialization ---
                    initWebGL(); // Set up shaders, program, buffers, etc.


                    // --- Optional: Message Hiding ---
                    setTimeout(function() {
                        const messageBox = document.querySelector('.message');
                        if (messageBox) {
                            messageBox.style.transition = 'opacity 1s ease-out';
                            messageBox.style.opacity = '0';
                            setTimeout(() => { if (messageBox) messageBox.style.display = 'none'; }, 1000);
                        }
                    }, 5000); // Hide after 5 seconds

                });
    </script>
</body>
</html>
