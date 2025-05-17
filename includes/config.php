<?php
// CONFIGURACIÓN DEFINITIVA PARA MAMP (puerto 3306)
$host = '127.0.0.1';  // Usar siempre 127.0.0.1 en MAMP
$dbname = 'dronesafe';
$username = 'root';
$password = 'root';

try {
    // Conexión con socket (la más estable en MAMP)
    $pdo = new PDO(
        "mysql:unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname=$dbname",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    die("<h2>Error de conexión MAMP</h2>"
        . "<b>Mensaje:</b> " . $e->getMessage() . "<br>"
        . "<b>Solución rápida:</b><ol>"
        . "<li>Verifica que MySQL esté en verde en MAMP</li>"
        . "<li>Ejecuta en terminal: <code>ls -la /Applications/MAMP/tmp/mysql/mysql.sock</code></li>"
        . "<li>Reinicia MAMP completamente</li></ol>");
}
?>