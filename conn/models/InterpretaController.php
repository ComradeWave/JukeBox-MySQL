<?php
// JukeBox PHPMySQL/conn/models/InterpretaController.php
class InterpretaController
{
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
    }

    /**
     * Add an interpretation (link between song and artist)
     *
     * @param int $id_canzone Song ID
     * @param int $id_cantante Artist ID
     * @return array Risultato dell'operazione
     */
    public function addInterpretazione($id_canzone, $id_cantante): array
    {
        // Validazione input
        if (!$id_canzone || !$id_cantante) {
            return [
                "success" => false,
                "message" => "ID canzone e artista sono obbligatori",
            ];
        }

        // Verifica esistenza canzone e artista
        $checkQuery = "SELECT
            (SELECT COUNT(*) FROM Canzone WHERE id = ?) as canzone_exists,
            (SELECT COUNT(*) FROM Cantante WHERE id = ?) as artista_exists";

        $checkStmt = mysqli_prepare($this->conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "ii", $id_canzone, $id_cantante);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $counts = mysqli_fetch_assoc($checkResult);

        if ($counts["canzone_exists"] == 0 || $counts["artista_exists"] == 0) {
            return [
                "success" => false,
                "message" => "Canzone o artista non esistono",
            ];
        }

        // Verifica se l'interpretazione esiste già
        $existQuery = "SELECT * FROM Interpreta
                       WHERE id_canzone = ? AND id_cantante = ?";
        $existStmt = mysqli_prepare($this->conn, $existQuery);
        mysqli_stmt_bind_param($existStmt, "ii", $id_canzone, $id_cantante);
        mysqli_stmt_execute($existStmt);
        $existResult = mysqli_stmt_get_result($existStmt);

        if (mysqli_num_rows($existResult) > 0) {
            return [
                "success" => false,
                "message" => "Interpretazione già esistente",
            ];
        }

        // Inserimento nuova interpretazione
        $query = "INSERT INTO Interpreta (id_canzone, id_cantante)
                  VALUES (?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return handleDatabaseError($this->conn, $query);
        }

        mysqli_stmt_bind_param($stmt, "ii", $id_canzone, $id_cantante);

        if (mysqli_stmt_execute($stmt)) {
            return [
                "success" => true,
                "message" => "Interpretazione aggiunta con successo",
                "id_canzone" => $id_canzone,
                "id_cantante" => $id_cantante,
            ];
        } else {
            return handleDatabaseError($this->conn, $query);
        }
    }

    /**
     * Get artists for a specific song
     *
     * @param int $id_canzone Song ID
     * @return array Risultato della query
     */
    public function getArtistiByCanzoneId($id_canzone): array
    {
        $query = "SELECT c.* FROM Cantante c
                  JOIN Interpreta i ON c.id = i.id_cantante
                  WHERE i.id_canzone = ?";
        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return handleDatabaseError($this->conn, $query);
        }

        mysqli_stmt_bind_param($stmt, "i", $id_canzone);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!$result) {
            return handleDatabaseError($this->conn, $query);
        }

        $artisti = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $artisti[] = $row;
        }

        return [
            "success" => true,
            "artisti" => $artisti,
            "count" => count($artisti),
        ];
    }

    /**
     * Get songs for a specific artist
     *
     * @param int $id_cantante Artist ID
     * @return array Risultato della query
     */
    public function getCanzoniByArtistId($id_cantante): array
    {
        $query = "SELECT c.* FROM Canzone c
                  JOIN Interpreta i ON c.id = i.id_canzone
                  WHERE i.id_cantante = ?";
        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return handleDatabaseError($this->conn, $query);
        }

        mysqli_stmt_bind_param($stmt, "i", $id_cantante);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!$result) {
            return handleDatabaseError($this->conn, $query);
        }

        $canzoni = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $canzoni[] = $row;
        }

        return [
            "success" => true,
            "canzoni" => $canzoni,
            "count" => count($canzoni),
        ];
    }

    /**
     * Remove an interpretation
     *
     * @param int $id_canzone Song ID
     * @param int $id_cantante Artist ID
     * @return array Risultato dell'operazione
     */
    public function removeInterpretazione($id_canzone, $id_cantante): array
    {
        // Verifica esistenza dell'interpretazione
        $checkQuery = "SELECT * FROM Interpreta
                       WHERE id_canzone = ? AND id_cantante = ?";
        $checkStmt = mysqli_prepare($this->conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "ii", $id_canzone, $id_cantante);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) == 0) {
            return [
                "success" => false,
                "message" => "Interpretazione non trovata",
            ];
        }

        $query = "DELETE FROM Interpreta
                  WHERE id_canzone = ? AND id_cantante = ?";
        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return handleDatabaseError($this->conn, $query);
        }

        mysqli_stmt_bind_param($stmt, "ii", $id_canzone, $id_cantante);

        if (mysqli_stmt_execute($stmt)) {
            return [
                "success" => true,
                "message" => "Interpretazione rimossa con successo",
                "id_canzone" => $id_canzone,
                "id_cantante" => $id_cantante,
            ];
        } else {
            return handleDatabaseError($this->conn, $query);
        }
    }
}
?>
