<?php
    // Database configuration
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'tictactoedb';
    
    // Create a PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);

    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>