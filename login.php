<?php
session_start(); // Start the session at the top
include_once "conn/config.php"; // Include database config

$login_error = ''; // Variable to hold login error messages

// 1. Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// 2. Process Login Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"])) || empty(trim($_POST["password"]))) {
        $login_error = "Per favore, inserisci username e password.";
    } else {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        $conn = createConnection(); // Create DB connection

        if ($conn) {
            $sql = "SELECT id, username, password_hash FROM utenti WHERE username = ?";
            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $param_username);
                $param_username = $username;

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);

                    // Check if username exists
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        mysqli_stmt_bind_result($stmt, $id, $db_username, $db_password_hash);
                        if (mysqli_stmt_fetch($stmt)) {
                            // Verify password
                            if (password_verify($password, $db_password_hash)) {
                                // Password is correct, start new session
                                session_regenerate_id(true); // Prevent session fixation

                                // Store data in session variables
                                $_SESSION["loggedin"] = true; // Simple flag
                                $_SESSION["user_id"] = $id;
                                $_SESSION["username"] = $db_username;

                                // Redirect user to index page
                                header("location: index.php");
                                exit;
                            } else {
                                // Display an error message if password is not valid
                                $login_error = "La password inserita non è valida.";
                            }
                        }
                    } else {
                        // Display an error message if username doesn't exist
                        $login_error = "Nessun account trovato con questo username.";
                    }
                } else {
                    $login_error = "Oops! Qualcosa è andato storto. Riprova più tardi.";
                    error_log("Login Error (Execute): " . mysqli_stmt_error($stmt));
                }
                mysqli_stmt_close($stmt);
            } else {
                $login_error = "Oops! Errore nella preparazione della query.";
                error_log("Login Error (Prepare): " . mysqli_error($conn));
            }
            closeConnection($conn); // Close connection
        } else {
            $login_error = "Oops! Impossibile connettersi al database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - JukeBox Management</title>
    <link rel="stylesheet" href="style.css"> <style>
        /* Basic Login Form Styles */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Full viewport height */
        }
        .login-container {
            background-color: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ff00;
            padding: 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 15px #00ff00;
            text-align: center;
        }
        .login-container h1 {
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        .login-container form div {
            margin-bottom: 15px;
            text-align: left;
        }
        .login-container label {
            display: block;
            margin-bottom: 5px;
            color: #00ff00;
            font-size: 0.9em;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%; /* Full width */
            padding: 10px;
            box-sizing: border-box; /* Include padding in width */
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            font-size: 1.1em;
        }
        .error-message {
            color: #ff6666; /* Light red for errors */
            background-color: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            padding: 10px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h1>JukeBox Login</h1>
    <p>Inserisci le tue credenziali per accedere.</p>

    <?php
    if(!empty($login_error)){
        echo '<div class="error-message">' . htmlspecialchars($login_error) . '</div>';
    }
    ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
        <div>
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div>
            <button type="submit">Accedi</button>
        </div>
    </form>
</div>
</body>
</html>