<?php
// JukeBox PHPMySQL/conn/api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

include_once "config.php";
include_once "models/Canzone.php";
include_once "models/Cantante.php";
include_once "controllers/SongsController.php";
include_once "controllers/ArtistsController.php";

$request_method = $_SERVER["REQUEST_METHOD"];
$request_uri = $_SERVER["REQUEST_URI"];

$songsController = new SongsController();
$artistsController = new ArtistsController();

switch ($request_method) {
    case "GET":
        if (strpos($request_uri, "/songs") !== false) {
            if (preg_match("/\/songs\/(\d+)/", $request_uri, $matches)) {
                echo $songsController->getSongById($matches[1]);
            } else {
                echo $songsController->getAllSongs();
            }
        } elseif (strpos($request_uri, "/artists") !== false) {
            echo $artistsController->getAllArtists();
        }
        break;

    case "POST":
        if (strpos($request_uri, "/songs") !== false) {
            $data = json_decode(file_get_contents("php://input"), true);
            echo json_encode($songsController->createSong($data));
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
        break;
}

?>
