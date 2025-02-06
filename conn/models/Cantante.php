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
        // Controllo duplicati
        $checkQuery = "SELECT id FROM {$this->table}
                      WHERE nome=? AND cognome=? AND data_nascita=?";

        // mysqli_prepare(): Creates a prepared statement for secure database queries
        // This method prepares an SQL statement template to prevent SQL injection
        // It separates the SQL query structure from actual data values

        // mysqli_stmt_bind_param(): Binds variables to the prepared statement
        // The first argument is the statement, second is a type string where:
        // 's' = string, 'i' = integer, 'd' = double, 'b' = blob
        // Subsequent arguments are the actual values to be bound

        // mysqli_stmt_execute(): Executes the prepared statement
        // Runs the query with the bound parameters, providing security against SQL injection
        // Returns true on success, false on failure
        $checkStmt = mysqli_prepare($this->conn, $checkQuery);
        mysqli_stmt_bind_param(
            $checkStmt,
            "sss",
            $this->nome,
            $this->cognome,
            $this->data_nascita
        );
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
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
