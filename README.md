# JukeBox PHP/MySQL Project

## Goal
Build a web application to manage a JukeBox database, allowing users to add, view, edit, and delete songs and artists, and manage the relationships between them (interpretations).

## Project Structure

* `/`
    * `index.php`: The main web interface for managing songs, artists, and interpretations.
    * `style.css`: Stylesheet for the web interface.
    * `README.md`: This file.
* `/conn`
    * `config.php`: Database connection setup.
    * `api.php`: (Potential) REST API endpoint definitions (Note: Current primary interaction is via `index.php`).
    * `/models`: Contains PHP classes for database entities (`Canzone.php`, `Cantante.php`) and controllers (`SongsController.php`, `ArtistController.php`, `InterpretaController.php`) handling business logic and database interactions.

## Database Schema

A MySQL database named `JukeBox` is used with the following tables:

1.  **`Cantante`**: Stores artist information.
    * `id` (PK)
    * `nome`
    * `cognome`
    * `data_nascita`
    * `nazionalità`
2.  **`Canzone`**: Stores song information.
    * `id` (PK)
    * `titolo`
    * `durata` (seconds)
    * `anno`
    * `genere`
    * `autore`
3.  **`Interpreta`**: Links songs to the artists who interpret them (Many-to-Many relationship).
    * `id_canzone` (FK -> Canzone.id)
    * `id_cantante` (FK -> Cantante.id)
    * `created_at` (Timestamp)
    * PRIMARY KEY (`id_canzone`, `id_cantante`)

```mermaid
erDiagram
    Canzone {
        int id PK
        string titolo
        int durata
        year anno
        string genere
        string autore
    }
    Cantante {
        int id PK
        string nome
        string cognome
        date data_nascita
        string nazionalita
    }
    Interpreta {
        int id_canzone FK
        int id_cantante FK
        timestamp created_at
    }
    Canzone ||--o{ Interpreta : "è interpretata da"
    Cantante ||--o{ Interpreta : interpreta
}```

Web Interface (index.php) Features

The index.php page provides a user interface to interact with the JukeBox database:

    Songs Management:
        View a list of all songs, including their author, year, and interpreters.
        Add new songs, including title, author, duration, year, genre, and selecting one or more interpreting artists.
        Edit existing songs (update details and change associated artists).
        Delete songs (also removes associated interpretations).
    Artists Management:
        View a list of all artists.
        Add new artists (name, birth date, nationality).
        Delete artists (also removes associated interpretations).
        (Note: Editing artists is not implemented in the current index.php)
    Interpretations Management:
        View interpreters associated with each song in the song list.
        Associate artists with a song: Select one song and multiple artists to create interpretation links.
        (Note: Direct removal of specific interpretations is done via editing the song or deleting the song/artist in index.php).

Controller Methods Overview

The /conn/models directory contains controllers that handle the application logic:

    SongsController.php: Manages song data (CRUD operations).
    ArtistController.php: Manages artist data (CRUD operations).
    InterpretaController.php: Manages the link between songs and artists (add/remove interpretations, retrieve artists for a song, retrieve songs for an artist).

(Potential) REST API Endpoints (conn/api.php)

* **Songs Management:**
    * View a list of all songs, including their author, year, and interpreters.
    * Add new songs, including title, author, duration, year, genre, and selecting one or more interpreting artists.
    * Edit existing songs (update details and change associated artists).
    * Delete songs using either:
        * Inline "Elimina" buttons next to each song in the list.
        * A dedicated "Cancella Canzone" section with a dropdown menu to select the song.
    * *(Note: Deleting a song also removes associated interpretations)*.

The conn/api.php file outlines potential REST endpoints. While the primary interaction currently happens through index.php, these endpoints could be used by other clients.

    GET /songs, GET /songs/{id}
    POST /songs
    PUT /songs/{id}
    DELETE /songs/{id}
    GET /artists, GET /artists/{id}
    POST /artists
    PUT /artists/{id}
    DELETE /artists/{id}
    GET /songs/{id}/artists
    GET /artists/{id}/songs
    POST /interpretazioni
    DELETE /interpretazioni (Requires id_canzone and id_cantante in body)

TODO / Future Improvements

    Implement artist editing functionality in index.php.
    Add robust input validation and sanitization on the server-side (controllers and index.php).
    Improve error handling and user feedback in index.php.
    Implement user authentication/authorization if needed.
    Add search/filtering capabilities to the lists in index.php.
    Consider using a templating engine (like Twig) for cleaner separation of PHP and HTML in index.php.
    Fully develop and document the REST API (api.php) if it's intended for external use.
    Add unit/integration tests.
    Implement logging.
