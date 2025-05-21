<?php
// Choose a strong password
$plainPassword = 'admin'; // Change this!
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
echo "Username: admin<br>";
echo "Password Hash: " . $hashedPassword;
?>