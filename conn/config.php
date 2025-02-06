<?php

function createConnection()
{
    $servername = "localhost"; // Aggiungi il punto e virgola mancante
    $username = "root"; // Racchiudi la stringa tra virgolette
    $password = ""; // Stringa vuota tra virgolette
    $dbname = "jukebox"; // Racchiudi la stringa tra virgolette

    // Create connection
    $conn = @mysqli_connect($servername, $username, $password, $dbname);
    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
        return false;
    }
    return $conn;
}

function closeConnection($conn)
{
    if ($conn) {
        mysqli_close($conn);
    }
}
?>
