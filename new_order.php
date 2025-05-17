<?php
session_start();
require 'includes/auth.php';
require 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickupLocation = $_POST['pickup_location'];
    $userId = $_SESSION['user_id']; // Asegúrate de obtener el ID del usuario logueado
    
    // Generar identificadores
    $trackingNumber = 'DS-' . time() . rand(100, 999);
    $droneId = 'DRN-' . strtoupper(substr(uniqid(), -6));
    $pickupCode = sprintf('%04d', rand(0, 9999)); // Código de 4 dígitos
    
    try {
        $stmt = $pdo->prepare("INSERT INTO packages 
                              (tracking_number, user_id, drone_id, pickup_code, status, 
                              estimated_delivery, pickup_location) 
                              VALUES (?, ?, ?, ?, 'processing', 
                              DATE_ADD(NOW(), INTERVAL 1 HOUR), ?)");
        $stmt->execute([
            $trackingNumber, 
            $userId, // Este es el campo crítico que debe vincularse
            $droneId, 
            $pickupCode,
            $pickupLocation
        ]);
        
        $packageId = $pdo->lastInsertId();
        $_SESSION['success'] = "Paquete creado correctamente. Código de recogida: $pickupCode";
        header('Location: tracking.php?tracking_id=' . $packageId);
        exit;
        
    } catch (PDOException $e) {
        $error = "Error al crear pedido: " . $e->getMessage();
    }
}
?>

<!-- Formulario HTML para crear nuevo pedido -->