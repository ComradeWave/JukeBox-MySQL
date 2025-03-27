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
    public function createSong($data): bool
    {
        $this->canzone->titolo = $data["titolo"];
        $this->canzone->durata = $data["durata"];
        $this->canzone->anno = $data["anno"];
        $this->canzone->genere = $data["genere"];
        $this->canzone->autore = $data["autore"];

        $songCreated = $this->canzone->create();
        if ($songCreated && isset($data["cantanti"]) && is_array($data["cantanti"]))
        {
            $song_id = mysqli_insert_id($this->conn); // Ottieni l'ID della canzone appena creata
            $interpretaController = new InterpretaController($this->conn);
            foreach ($data["cantanti"] as $cantante_id)
            {
                $interpretaController->addInterpretazione($song_id, $cantante_id);
            }
        }
        return $songCreated;
    }

    /**
     * Update an existing song
     *
     * @param int $id Song ID
     * @param array $data Updated song details
     * @return bool True on successful update
     */
    public function updateSong($id, $data): bool
    {
        $this->canzone->id = $id;
        $this->canzone->titolo = $data["titolo"];
        $this->canzone->durata = $data["durata"];
        $this->canzone->anno = $data["anno"];
        $this->canzone->genere = $data["genere"];
        $this->canzone->autore = $data["autore"];

        return $this->canzone->update();
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
