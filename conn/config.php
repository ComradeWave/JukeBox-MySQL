<?php

function createConnection()
{
    $servername = "localhost"; // Aggiungi il punto e virgola mancante
    $username = "root"; // Racchiudi la stringa tra virgolette
    $password = ""; // Stringa vuota tra virgolette
    $dbname = "JukeBox"; // Racchiudi la stringa tra virgolette

    // Create connection
    $conn = @mysqli_connect($servername, $username, $password, $dbname);
    $conn->set_charset("utf8");
    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}

function closeConnection($conn)
{
    if ($conn) {
        mysqli_close($conn);
    }
}
function handleDatabaseError($conn, $query = null): array
{
    $errorMessage = mysqli_error($conn);
    $errorCode = mysqli_errno($conn);

    error_log("Database Error [Code $errorCode]: $errorMessage");

    if ($query) {
        error_log("Query that caused the error: $query");
    }

    return [
        "success" => false,
        "error_code" => $errorCode,
        "error_message" => $errorMessage,
    ];
}
?>
