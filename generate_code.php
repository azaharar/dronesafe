<?php
session_start();
require 'includes/auth.php';
require 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'No autorizado']));
}

if (!isset($_GET['package_id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'ID de paquete no proporcionado']));
}

$packageId = $_GET['package_id'];
$userId = $_SESSION['user_id'];

try {
    // Verificar que el paquete pertenece al usuario y está en tránsito
    $stmt = $pdo->prepare("SELECT id, status FROM packages WHERE id = ? AND user_id = ? AND status = 'in_transit'");
    $stmt->execute([$packageId, $userId]);
    $package = $stmt->fetch();

    if (!$package) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Paquete no encontrado o no está en tránsito']));
    }

    // Generar código de 4 dígitos
    $code = sprintf('%04d', rand(0, 9999));

    // Actualizar el paquete
    $updateStmt = $pdo->prepare("UPDATE packages 
                                SET status = 'ready_for_pickup', 
                                    pickup_code = ?,
                                    actual_delivery = NOW()
                                WHERE id = ?");
    $updateStmt->execute([$code, $packageId]);

    echo json_encode(['success' => true, 'code' => $code]);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Error en la base de datos']));
}