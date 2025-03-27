<?php
// JukeBox PHPMySQL/conn/models/Canzone.php
if (!class_exists('Canzone')) {
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
            mysqli_stmt_bind_param(
                $checkStmt,
                "ssi",
                $this->titolo,
                $this->autore,
                $this->anno
            );
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);

            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                // Canzone già presente
                return false;
            }

            $query = "INSERT INTO {$this->table}
                    (titolo, durata, anno, genere, autore)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param(
                $stmt,
                "sisis",
                $this->titolo,
                $this->durata,
                $this->anno,
                $this->genere,
                $this->autore
            );

            return mysqli_stmt_execute($stmt);
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
            $query = "DELETE FROM {$this->table} WHERE id=?";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $this->id);
            return mysqli_stmt_execute($stmt);
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