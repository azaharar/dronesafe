<?php
$host = 'localhost:3306';  // Puerto estándar de MySQL
$dbname = 'dronesafe';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);  // Cambiado aquí
    $pdo->exec("SET NAMES 'utf8mb4'");
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>



