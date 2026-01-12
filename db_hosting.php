<?php
$servername = "localhost"; 
$username   = "u316800465_gymfit";   
$password   = "Gymfitcaps1122";  
$dbname     = "u316800465_Gymfitdb";    

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Hosting DB connection failed: " . $conn->connect_error);
}
?>
