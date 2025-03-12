<?php
// JukeBox PHPMySQL/conn/controllers/ArtistsController.php
class ArtistsController
{
    /**
     * Cantante model instance
     *
     * @var Cantante
     */
    private $cantante;

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
        $this->cantante = new Cantante($conn);
    }

    /**
     * Retrieve all artists as JSON
     *
     * @return string JSON encoded array of artists
     */
    public function getAllArtists(): string
    {
        $result = $this->cantante->read();
        $artists = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Optional: Add additional processing
            $row["full_name"] = $row["nome"] . " " . $row["cognome"];
            $artists[] = $row;
        }
        return json_encode($artists);
    }

    /**
     * Retrieve a specific artist by ID
     *
     * @param int $id Artist ID
     * @return string JSON encoded artist details
     */
    public function getArtistById($id): string
    {
        $artist = $this->cantante->getArtistById($id);

        if ($artist) {
            // Additional artist details retrieval
            $artist["songs"] = $this->getArtistSongs($id);
        }

        return json_encode($artist);
    }

    /**
     * Retrieve songs by a specific artist
     *
     * @param int $id Artist ID
     * @return array List of songs by the artist
     */
    private function getArtistSongs($id): array
    {
        $query = "SELECT c.id, c.titolo, c.anno, c.genere
                  FROM Canzone c
                  JOIN Interpreta i ON c.id = i.id_canzone
                  WHERE i.id_cantante = ?";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $songs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $songs[] = $row;
        }

        return $songs;
    }

    /**
     * Create a new artist
     *
     * @param array $data Artist details
     * @return bool True if artist creation was successful
     */
    public function createArtist($data): bool
    {
        // Validate input
        $requiredFields = ["nome", "cognome", "data_nascita", "nazionalità"];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        $this->cantante->nome = $data["nome"];
        $this->cantante->cognome = $data["cognome"];
        $this->cantante->data_nascita = $data["data_nascita"];
        $this->cantante->nazionalità = $data["nazionalità"];

        // Optional fields
        $this->cantante->genere_principale = $data["genere_principale"] ?? null;
        $this->cantante->biografia = $data["biografia"] ?? null;

        return $this->cantante->create();
    }

    /**
     * Update an existing artist
     *
     * @param int $id Artist ID
     * @param array $data Updated artist details
     * @return bool True if artist update was successful
     */
    public function updateArtist($id, $data): bool
    {
        // Fetch existing artist to merge with new data
        $existingArtist = $this->cantante->getArtistById($id);

        if (!$existingArtist) {
            return false;
        }

        $this->cantante->id = $id;
        $this->cantante->nome = $data["nome"] ?? $existingArtist["nome"];
        $this->cantante->cognome =
            $data["cognome"] ?? $existingArtist["cognome"];
        $this->cantante->data_nascita =
            $data["data_nascita"] ?? $existingArtist["data_nascita"];
        $this->cantante->nazionalità =
            $data["nazionalità"] ?? $existingArtist["nazionalità"];

        // Optional fields
        $this->cantante->genere_principale =
            $data["genere_principale"] ?? $existingArtist["genere_principale"];
        $this->cantante->biografia =
            $data["biografia"] ?? $existingArtist["biografia"];

        return $this->cantante->update();
    }

    /**
     * Delete an artist
     *
     * @param int $id Artist ID to delete
     * @return bool True if artist deletion was successful
     */
    public function deleteArtist($id): bool
    {
        // Optional: Check if artist exists before deletion
        $existingArtist = $this->cantante->getArtistById($id);

        if (!$existingArtist) {
            return false;
        }

        // Optional: Check if artist has associated songs
        $songsQuery =
            "SELECT COUNT(*) as song_count FROM Interpreta WHERE id_cantante = ?";
        $stmt = mysqli_prepare($this->conn, $songsQuery);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $songCount = mysqli_fetch_assoc($result)["song_count"];

        if ($songCount > 0) {
            // Optionally, you could choose to:
            // 1. Prevent deletion
            // return false;

            // 2. Delete associated songs
            $deleteAssociatedSongs =
                "DELETE FROM Interpreta WHERE id_cantante = ?";
            $stmt = mysqli_prepare($this->conn, $deleteAssociatedSongs);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
        }

        $this->cantante->id = $id;
        return $this->cantante->delete();
    }

    /**
     * Search artists by name or nationality
     *
     * @param string $query Search query
     * @return string JSON encoded array of matching artists
     */
    public function searchArtists($query): string
    {
        $searchQuery = "%{$query}%";
        $sql = "SELECT * FROM Cantante
                WHERE nome LIKE ? OR cognome LIKE ? OR nazionalità LIKE ?";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $searchQuery,
            $searchQuery,
            $searchQuery
        );
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $artists = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $row["full_name"] = $row["nome"] . " " . $row["cognome"];
            $artists[] = $row;
        }

        return json_encode($artists);
    }
}
?>
