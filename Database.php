<?php
// JukeBox PHPMySQL/conn/Database.php
class Database
{
    public function getConnection(): mysqli
    {
        global $conn;
        return $conn;
    }
}
?>
