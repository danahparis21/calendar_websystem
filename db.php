<?php
$servername = "localhost"; // Database host (usually localhost)
$username = "root"; // Your MySQL username (default is 'root')
$password = ""; // Your MySQL password (usually empty for localhost)
$dbname = "calendar_system"; // The name of your database

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error()); // If there's an error with the connection, stop and show the error
}
?>
