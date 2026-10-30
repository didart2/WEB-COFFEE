<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'grade2';

$conn = new mysqli($host, $user, $pass, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
?>
