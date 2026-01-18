<?php
// Type the password you want to hash here:
$password = "password123";

// Generate hashed password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Display result
echo "Original Password: " . $password . "<br>";
echo "Hashed Password: " . $hashed;
?>
