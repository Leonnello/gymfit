<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "gymfitdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Local DB connection failed: " . $conn->connect_error);
}
?>
