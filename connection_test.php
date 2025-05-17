<?php
require 'includes/config.php';
$stmt = $pdo->query("SHOW TABLES");
echo "<h2>Tablas en la base de datos:</h2>";
print_r($stmt->fetchAll());