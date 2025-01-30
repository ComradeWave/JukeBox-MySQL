<?php
// JukeBox PHPMySQL/conn/models/Cantante.php
class Cantante
{
    /**
     * Database connection object
     *
     * @var mysqli
     */
    private $conn;

    /**
     * Database table name
     *
     * @var string
     */
    private $table = "Cantante";

    /**
     * Artist ID
     *
     * @var int
     */
    public $id;

    /**
     * Artist first name
     *
     * @var string
     */
    public $nome;

    /**
     * Artist last name
     *
     * @var string
     */
    public $cognome;

    /**
     * Artist birth date
     *
     * @var string
     */
    public $data_nascita;

    /**
     * Artist nationality
     *
     * @var string
     */
    public $nazionalità;

    /**
     * Constructor
     *
     * @param mysqli $conn Database connection object
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Retrieve all artists from the database
     *
     * @return mysqli_result|bool Query result or false on failure
     */
    public function read(): mysqli_result|bool
    {
        $query = "SELECT * FROM {$this->table}";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }

    /**
     * Create a new artist in the database
     *
     * @return bool True on successful insertion, false otherwise
     */
    public function create(): bool
    {
        $query = "INSERT INTO {$this->table}
                  (nome, cognome, data_nascita, nazionalità)
                  VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $this->nome,
            $this->cognome,
            $this->data_nascita,
            $this->nazionalità
        );

        return mysqli_stmt_execute($stmt);
    }

    /**
     * Update an existing artist in the database
     *
     * @return bool True on successful update, false otherwise
     */
    public function update(): bool
    {
        $query = "UPDATE {$this->table}
                  SET nome=?, cognome=?, data_nascita=?, nazionalità=?
                  WHERE id=?";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $this->nome,
            $this->cognome,
            $this->data_nascita,
            $this->nazionalità,
            $this->id
        );

        return mysqli_stmt_execute($stmt);
    }

    /**
     * Delete an artist from the database
     *
     * @return bool True on successful deletion, false otherwise
     */
    public function delete(): bool
    {
        $query = "DELETE FROM {$this->table} WHERE id=?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $this->id);
        return mysqli_stmt_execute($stmt);
    }

    /**
     * Retrieve a specific artist by their ID
     *
     * @param int $id The ID of the artist to retrieve
     * @return array|null Associative array of artist details or null if not found
     */
    public function getArtistById($id): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
}
?>
