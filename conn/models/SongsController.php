<?php
class SongsController
{
    /**
     * Canzone model instance
     *
     * @var Canzone
     */
    private $canzone;

    /**
     * Database connection
     *
     * @var mysqli
     */
    private $conn;

    /**
     * Constructor
     *
     * @param mysqli $conn Database connection object
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
        include_once "Canzone.php";
        $this->canzone = new Canzone($conn);
    }

    /**
     * Retrieve all songs as JSON
     *
     * @return string JSON encoded array of songs
     */
    public function getAllSongs(): string
    {
        $result = $this->canzone->read();
        $songs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $songs[] = $row;
        }
        return json_encode($songs);
    }

    /**
     * Retrieve a specific song by ID
     *
     * @param int $id Song ID
     * @return string JSON encoded song details
     */
    public function getSongById($id): string
    {
        $song = $this->canzone->getSongById($id);
        return json_encode($song);
    }

    /**
     * Create a new song
     *
     * @param array $data Song details
     * @return bool True on successful creation
     */
    public function createSong($data): int
    {
        $this->canzone->titolo = $data["titolo"];
        $this->canzone->durata = $data["durata"];
        $this->canzone->anno = $data["anno"];
        $this->canzone->genere = $data["genere"];
        $this->canzone->autore = $data["autore"];

        if ($this->canzone->create()) {
            $songId = mysqli_insert_id($this->conn);

            if (isset($data["artisti"]) && is_array($data["artisti"])) {
                $interpretaController = new InterpretaController($this->conn);
                foreach ($data["artisti"] as $artistId) {
                    $interpretaController->addInterpretazione(
                        $songId,
                        $artistId
                    );
                }
            }

            return $songId;
        }

        return 0; // Or handle the failure appropriately
    }

    /**
     * Update an existing song
     *
     * @param int $id Song ID
     * @param array $data Updated song details
     * @return bool True on successful update
     */
    public function updateSong(int $id, array $data): bool
    {
        $this->canzone->id = $id;
        $this->canzone->titolo = $data["titolo"];
        $this->canzone->durata = $data["durata"];
        $this->canzone->anno = $data["anno"];
        $this->canzone->genere = $data["genere"];
        $this->canzone->autore = $data["autore"];

        $songUpdated = $this->canzone->update();

        if (
            $songUpdated &&
            isset($data["artisti"]) &&
            is_array($data["artisti"])
        ) {
            $interpretaController = new InterpretaController($this->conn);

            // Ottieni gli artisti correnti della canzone
            $currentArtists = $interpretaController->getArtistiByCanzoneId($id)[
                "artisti"
            ];
            $currentArtistIds = array_column($currentArtists, "id");

            $newArtistIds = $data["artisti"];

            // Artisti da aggiungere
            $artistsToAdd = array_diff($newArtistIds, $currentArtistIds);
            foreach ($artistsToAdd as $artistId) {
                $interpretaController->addInterpretazione($id, $artistId);
            }

            // Artisti da rimuovere
            $artistsToRemove = array_diff($currentArtistIds, $newArtistIds);
            foreach ($artistsToRemove as $artistId) {
                $interpretaController->removeInterpretazione($id, $artistId);
            }
        }

        return $songUpdated;
    }

    /**
     * Delete a song
     *
     * @param int $id Song ID to delete
     * @return bool True on successful deletion
     */
    public function deleteSong($id): bool
    {
        $this->canzone->id = $id;
        return $this->canzone->delete();
    }
}
?>
