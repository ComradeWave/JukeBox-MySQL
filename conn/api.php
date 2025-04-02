<?php
// JukeBox PHPMySQL/conn/api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

include_once "config.php";
include_once "models/Canzone.php";
include_once "models/Cantante.php";
include_once "models/InterpretaController.php"; // Aggiungi questa riga
include_once "controllers/SongsController.php";
include_once "controllers/ArtistsController.php";

// Crea la connessione
$conn = createConnection();

$songsController = new SongsController($conn);
$artistsController = new ArtistsController($conn);
$interpretiController = new InterpretaController($conn); // Aggiungi questa riga

$request_method = $_SERVER["REQUEST_METHOD"];
$request_uri = $_SERVER["REQUEST_URI"];

switch ($request_method) {
    case "GET":
        if (str_contains($request_uri, "/songs")) {
            if (preg_match("/\/songs\/(\d+)/", $request_uri, $matches)) {
                echo $songsController->getSongById($matches[1]);
            } else {
                echo $songsController->getAllSongs();
            }
        } elseif (str_contains($request_uri, "/artists")) {
            echo $artistsController->getAllArtists();
        }
        // Nuovi endpoint per interpretazioni
        elseif (
            preg_match("/\/songs\/(\d+)\/artists/", $request_uri, $matches)
        ) {
            $result = $interpretiController->getArtistiByCanzoneId($matches[1]);
            echo json_encode($result);
        } elseif (
            preg_match("/\/artists\/(\d+)\/songs/", $request_uri, $matches)
        ) {
            $result = $interpretiController->getCanzoniByArtistId($matches[1]);
            echo json_encode($result);
        }
        break;

    case "POST":
        if (strpos($request_uri, "/songs") !== false) {
            $data = json_decode(file_get_contents("php://input"), true);
            $songId = $songsController->createSong($data);
            if ($songId) {
                http_response_code(201); // Created
                echo json_encode(["success" => true, "id" => $songId]);
            } else {
                http_response_code(500); // Internal Server Error
                echo json_encode([
                    "success" => false,
                    "message" => "Errore nella creazione della canzone",
                ]);
            }
        }
        // Nuovo endpoint per interpretazioni
        elseif (str_contains($request_uri, "/interpretazioni")) {
            $data = json_decode(file_get_contents("php://input"), true);
            $result = $interpretiController->addInterpretazione(
                $data["id_canzone"],
                $data["id_cantante"]
            );
            echo json_encode($result);
        }
        break;

    case "PUT":
        if (preg_match("/\/songs\/(\d+)/", $request_uri, $matches)) {
            $data = json_decode(file_get_contents("php://input"), true);
            echo json_encode($songsController->updateSong($matches[1], $data));
        }
        break;

    case "DELETE":
        if (preg_match("/\/songs\/(\d+)/", $request_uri, $matches)) {
            echo json_encode($songsController->deleteSong($matches[1]));
        }
        // Nuovo endpoint per rimuovere interpretazioni
        elseif (strpos($request_uri, "/interpretazioni") !== false) {
            $data = json_decode(file_get_contents("php://input"), true);
            $result = $interpretiController->removeInterpretazione(
                $data["id_canzone"],
                $data["id_cantante"]
            );
            echo json_encode($result);
        }
        break;
}

// Chiudi la connessione alla fine
closeConnection($conn);
?>
