# JukeBox PHP/MariaDB Project

## Goal
Build a web application to manage a JukeBox database, requiring user login to add, view, edit, and delete songs and artists, and manage the relationships between them (interpretations).

## Project Structure

* `/`
    * `index.php`: The main web interface for managing songs, artists, and interpretations (Requires Login).
    * `login.php`: User login page.
    * `logout.php`: Handles user logout.
    * `style.css`: Consolidated stylesheet for the web interface.
    * `README.md`: This file.
* `/conn`
    * `config.php`: Database connection setup.
    * `api.php`: (Potential) REST API endpoint definitions (Note: Primary interaction is via `index.php`).
    * `/models`: Contains PHP classes for database entities (`Canzone.php`, `Cantante.php`) and controllers (`SongsController.php`, `ArtistController.php`, `InterpretaController.php`) handling business logic and database interactions.

## Database Schema

A MariaDB/MySQL database named `JukeBox` is used with the following tables:

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
    * `autore` (Note: Currently not displayed/actively used in main table of `index.php`)
3.  **`Interpreta`**: Links songs to the artists who interpret them (Many-to-Many relationship).
    * `id_canzone` (FK -> Canzone.id)
    * `id_cantante` (FK -> Cantante.id)
    * `created_at` (Timestamp)
    * PRIMARY KEY (`id_canzone`, `id_cantante`)
4.  **`utenti`**: Stores user login information.
    * `id` (PK, AUTO_INCREMENT)
    * `username` (VARCHAR, UNIQUE)
    * `password_hash` (VARCHAR) - Stores securely hashed passwords.
    * `created_at` (TIMESTAMP)
### Still thinking about the table users, as it wasn't planned, will see if i revert it
# JukeBox Web Interface (`index.php`) Features

This document describes the features and layout of the main web interface for the JukeBox Management application, accessible after logging in via `login.php`.

## Core Layout & Features

* **Authentication:** Access to `index.php` is protected. Users must log in via `login.php`. If not logged in, they are redirected to the login page.
* **Welcome & Logout:** Displays a welcome message with the logged-in user's username (`$_SESSION["username"]`) and provides a "Logout" link pointing to `logout.php`.
* **Feedback Messages:** A designated area at the top displays status messages (success or error) after operations are performed (e.g., "Canzone aggiunta con successo!").
* **Collapsible Sections:** Major content areas like the song list and artist list are wrapped in collapsible sections (`div.collapsible-section`) allowing users to show/hide them.
* **Styling:** Uses the external `style.css` stylesheet for a consistent retro terminal look.
* **Background:** Features a WebGL shader background (`#bg-shader`).

## Feature Sections

### 1. Ricerca Globale (Global Search)

* A central search input (`#search-input`) allows users to filter the displayed songs and artists.
* Submitting the search reloads the page with a `?search=` parameter, triggering server-side filtering of the data before the lists are displayed.
* A "Mostra Tutto" (Show All) link appears next to the search button when a search is active, allowing the user to clear the search filter.

### 2. Aggiungi/Modifica Canzone (Add/Edit Song)

* **Conditional Display:** Displays either the "Aggiungi Canzone" form or the "Modifica Canzone" form based on the `$_GET['action']` parameter (`action=edit_song`).
* **Add Song Form (`action=add_song`):**
    * Inputs for Titolo, Durata (sec), Anno, Genere.
    * A multiple-select dropdown (`#add-cantanti`) to choose one or more interpreting artists. Selecting artists is **optional**.
    * Submit button triggers the `add_song` action.
* **Edit Song Form (`action=edit_song`):**
    * Populated with the details of the song being edited (fetched based on `$_GET['id']`).
    * Inputs for Titolo, Autore, Durata, Anno, Genere.
    * Multiple-select dropdown (`#edit-cantanti`) pre-selects the current interpreters for the song. Users can change the selection.
    * Submit button triggers the `update_song` action.
    * "Annulla Modifica" (Cancel Edit) link returns to the main view.

### 3. Canzoni (Songs List)

* Displayed within a collapsible section (`#songs-section`).
* A table (`#songs-table`) lists songs retrieved from the database (filtered by global search, if active).
* **Columns:** Titolo, Anno, Interpreti, Azioni. (Autore data exists but is not displayed in the table).
* **Interpreti Column:**
    * Lists the names of artists who interpret the song.
    * Displays each interpreter with a small "×" button (`.button-delete-interpretation`) next to their name. Clicking this button triggers the `delete_interpretation` action for that specific song-artist link, after confirmation.
* **Azioni Column:**
    * "Modifica" link: Takes the user to the Edit Song form for that song (`?action=edit_song&id=...`).
    * "Elimina Canzone" button: Submits a form to delete the entire song (triggers `delete` action with `delete_type=song`), after confirmation.

### 4. Aggiungi Artista (Add Artist)

* A simple form for adding new artists.
* Inputs for Nome, Cognome, Data di Nascita, Nazionalità.
* Submit button triggers the `add_artist` action.

### 5. Artisti (Artists List)

* Displayed within a collapsible section (`#artists-section`).
* A table (`#artists-table`) lists artists retrieved from the database (filtered by global search, if active).
* **Columns:** Nome, Cognome, Nazionalità, Azioni.
* **Azioni Column:**
    * "Elimina" button: Submits a form to delete the artist (triggers `delete` action with `delete_type=artist`), after confirmation.

### 6. Gestisci Interpretazioni (Manage Interpretations)

* A form section to associate existing artists with an existing song.
* A dropdown (`#interp-song-select`) to select the target song. The displayed text format is `TITOLO (INTERPRETI, ANNO)`.
* A multiple-select dropdown (`#interp-artist-select`) to choose one or more artists to associate with the selected song.
* Submit button triggers the `add_interpretation` action, creating links in the `Interpreta` table for the selected song and artists.

### 7. Cancella Artista (con ricerca) (Delete Artist with Search)

* Provides an alternative way to delete artists.
* An input field (`#delete-artist-search`) with autocomplete suggestions for artists.
* Suggestions appear in a div (`#delete-artist-suggestions`) as the user types.
* Clicking a suggestion populates a hidden field (`#delete-artist-id`) and enables the delete button.
* Submit button triggers the `delete` action (`delete_type=artist`) for the selected artist ID, after confirmation.

### 8. Cancella Canzone (con ricerca) (Delete Song with Search)

* Provides an alternative way to delete songs.
* An input field (`#delete-song-search`) with autocomplete suggestions for songs (based on title, year, genre).
* Suggestions appear in a div (`#delete-song-suggestions`) as the user types.
* Clicking a suggestion populates a hidden field (`#delete-song-id`) and enables the delete button.
* Submit button triggers the `delete` action (`delete_type=song`) for the selected song ID, after confirmation.
