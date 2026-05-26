<?php
// create_hash.php - Run this file once to generate the correct password hash
echo "Password hash for 'northcom':<br>";
echo password_hash('northcom', PASSWORD_DEFAULT);
echo "<br><br>";
echo "Copy this hash and update your database.";
?>