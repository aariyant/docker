<?php
$conn = new mysqli("mysql", "root", "rootpass", "appdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected to MySQL successfully!";
?>
