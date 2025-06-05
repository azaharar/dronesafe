<?php
$host = 'localhost:3306';  
$dbname = 'dronesafe';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);  
    $pdo->exec("SET NAMES 'utf8mb4'");
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>



