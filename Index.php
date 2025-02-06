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
    <title>JukeBox Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            display: flex;
            justify-content: space-between;
        }
        .section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 45%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        form input, form select {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
        }
        .message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>JukeBox Management</h1>

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
