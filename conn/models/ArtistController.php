<?php
// JukeBox PHPMySQL/conn/controllers/ArtistsController.php
class ArtistsController
{
    private $cantante;

    public function __construct()
    {
        $this->cantante = new Cantante();
    }

    public function getAllArtists(): string|bool
    {
        $result = $this->cantante->read();
        $artists = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $artists[] = $row;
        }
        return json_encode($artists);
    }

    // Other methods similarly updated
    //TODO: Implement methods
}
?>
