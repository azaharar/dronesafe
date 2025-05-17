<?php
require 'includes/config.php';
require 'includes/auth.php';

// Datos de prueba
$test_user = [
    'username' => 'test_'.rand(100,999),
    'email' => 'test_'.rand(100,999).'@example.com',
    'password' => 'Test1234!'
];

$result = registerUser($test_user['username'], $test_user['email'], $test_user['password']);

if ($result) {
    echo "<h2>¡Registro exitoso!</h2>";
    echo "<pre>Usuario creado con ID: $result\n";
    print_r($test_user);
} else {
    echo "<h2>Error en registro</h2>";
    echo "Revisa el archivo error_log en: /Applications/MAMP/logs/php_error.log";
}