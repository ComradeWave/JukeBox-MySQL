<?php
// JukeBox PHPMySQL/conn/models/Canzone.php
if (!class_exists("Canzone")) {
    class Canzone
    {
        private $conn;
        private $table = "Canzone";

        public $id;
        public $titolo;
        public $durata;
        public $anno;
        public $genere;
        public $autore;

        public function __construct($conn)
        {
            $this->conn = $conn;
        }

        // (Other methods: read, create, update, delete, getSongById)
        public function read(): mysqli_result|bool
        {
            $query = "SELECT * FROM {$this->table}";
            $result = mysqli_query($this->conn, $query);
            return $result;
        }

        public function create(): bool
        {
            //Controllo duplicati
            $checkQuery = "SELECT id FROM {$this->table}
                        WHERE titolo=? AND autore=? AND anno = ?";

            $checkStmt = mysqli_prepare($this->conn, $checkQuery);
            // Ensure anno is bound as integer here too if check involves it directly
            mysqli_stmt_bind_param(
                $checkStmt,
                "ssi", // Assuming autore is string, anno is integer
                $this->titolo,
                $this->autore, // Autore might be empty string based on index.php logic now
                $this->anno
            );
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);

            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                // Canzone già presente
                error_log("Attempted to add duplicate song: Title='{$this->titolo}', Author='{$this->autore}', Year='{$this->anno}'");
                return false;
            }
            mysqli_stmt_close($checkStmt); // Close the check statement

            $query = "INSERT INTO {$this->table}
                    (titolo, durata, anno, genere, autore)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($this->conn, $query);
            if (!$stmt) {
                error_log("Prepare failed for insert song: (" . $this->conn->errno . ") " . $this->conn->error);
                return false;
            }

            // Correct the type definition string here:
            mysqli_stmt_bind_param(
                $stmt,
                "siiss", // Correct types: string, integer, integer, string, string
                $this->titolo,  // s
                $this->durata,  // i
                $this->anno,    // i
                $this->genere,  // s <- Now correctly bound as string
                $this->autore   // s
            );

            $success = mysqli_stmt_execute($stmt);
            if (!$success) {
                error_log("Execute failed for insert song: (" . $stmt->errno . ") " . $stmt->error);
            }
            mysqli_stmt_close($stmt); // Close the main statement
            return $success;
        }

        public function update(): bool
        {
            $query = "UPDATE {$this->table}
                    SET titolo=?, durata=?, anno=?, genere=?, autore=?
                    WHERE id=?";

            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param(
                $stmt,
                "sisisi",
                $this->titolo,
                $this->durata,
                $this->anno,
                $this->genere,
                $this->autore,
                $this->id
            );

            return mysqli_stmt_execute($stmt);
        }

        public function delete(): bool
        {
            // First, delete related interpretations
            $queryInterpret = "DELETE FROM Interpreta WHERE id_canzone = ?"; // <-- THIS LINE
            $stmtInterpret = mysqli_prepare($this->conn, $queryInterpret);
            mysqli_stmt_bind_param($stmtInterpret, "i", $this->id);
            $interpretDeleted = mysqli_stmt_execute($stmtInterpret); // Execute and check result (optional but good)
            mysqli_stmt_close($stmtInterpret); // Close statement

            // Only proceed if interpretations were deleted (or if none existed)
            // You might want more robust error handling here based on $interpretDeleted result
            // if (!$interpretDeleted) {
            //     error_log("Failed to delete interpretations for song ID: " . $this->id);
            //     // return false; // Decide if failure to delete interpretations should stop song deletion
            // }

            // Then, delete the song itself
            $querySong = "DELETE FROM {$this->table} WHERE id=?";
            $stmtSong = mysqli_prepare($this->conn, $querySong);
            mysqli_stmt_bind_param($stmtSong, "i", $this->id);
            $songDeleted = mysqli_stmt_execute($stmtSong);
            mysqli_stmt_close($stmtSong); // Close statement

            return $songDeleted; // Return the result of deleting the song
        }
        /**
         * Retrieve a song by its ID
         *
         * @param int $id The ID of the song to retrieve
         * @return array|bool|null The song data or false if the song is not found
         */
        public function getSongById($id): array|bool|null
        {
            $query = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            return mysqli_fetch_assoc($result);
        }
    }
}
?>
