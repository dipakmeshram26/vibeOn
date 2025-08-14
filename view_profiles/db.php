<?php
$host = "localhost"; 
$user = "root"; // अगर तुमने MySQL में दूसरा username रखा है तो यहाँ बदलना
$pass = "";     // अगर password सेट है तो यहाँ डालना
$dbname = "vibeon";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
