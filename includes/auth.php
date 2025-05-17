<?php
require 'config.php';

/**
 * Genera un número de seguimiento único
 */
function generateUniqueTrackingNumber($pdo) {
    do {
        $trackingNumber = 'DS' . time() . rand(1000, 9999);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM packages WHERE tracking_number = ?");
        $stmt->execute([$trackingNumber]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);
    return $trackingNumber;
}

/**
 * Registra un nuevo usuario en la base de datos
 */
function registerUser($username, $email, $password) {
    global $pdo;

    try {
        // Validaciones previas
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if (strlen($username) < 4) {
            throw new Exception("El usuario debe tener al menos 4 caracteres");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Formato de email inválido");
        }

        if (strlen($password) < 8) {
            throw new Exception("La contraseña debe tener mínimo 8 caracteres");
        }

        // Verificar si el usuario/email ya existen
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            throw new Exception("El usuario o email ya están registrados");
        }

        // Hash seguro de la contraseña
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insertar nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);

        $userId = $pdo->lastInsertId(); // Obtener el ID del usuario recién insertado

        // Generar número de seguimiento único
        $trackingNumber = generateUniqueTrackingNumber($pdo);

        // Crear un paquete inicial para el usuario
        $droneId = 'DRN' . rand(1000, 9999);
        $pickupCode = sprintf('%04d', rand(0, 9999)); // Código de recogida aleatorio de 4 dígitos

        $stmt = $pdo->prepare("INSERT INTO packages (tracking_number, user_id, drone_id, pickup_code, status, estimated_delivery, pickup_location) 
                              VALUES (?, ?, ?, ?, 'in_transit', DATE_ADD(NOW(), INTERVAL 30 MINUTE), 'Centro de Distribución')");
        $stmt->execute([$trackingNumber, $userId, $droneId, $pickupCode]);

        return $userId; // Devolvemos el ID del usuario

    } catch (PDOException $e) {
        error_log("Error de base de datos: " . $e->getMessage());
        throw new Exception("Error al procesar el registro");
    } catch (Exception $e) {
        error_log("Error de validación: " . $e->getMessage());
        throw $e; // Re-lanzamos para manejar en el controlador
    }
}

/**
 * Autentica a un usuario
 */
function loginUser($username, $password) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }

        return false;

    } catch (PDOException $e) {
        error_log("Error de login: " . $e->getMessage());
        throw new Exception("Error al iniciar sesión");
    }
}

/**
 * Verifica si hay una sesión activa
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Obtiene los paquetes de un usuario
 */
function getUserPackages($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM packages WHERE user_id = ? ORDER BY estimated_delivery DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener paquetes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene la ubicación de un dron
 */
function getDroneLocation($droneId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM drone_locations WHERE drone_id = ? ORDER BY timestamp DESC LIMIT 1");
        $stmt->execute([$droneId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener ubicación: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica un código de recogida
 */
function verifyPickupCode($packageId, $code) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND pickup_code = ? LIMIT 1");
        $stmt->execute([$packageId, $code]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error en verificación: " . $e->getMessage());
        return false;
    }
}

/**
 * Marca un paquete como entregado
 */
function markAsDelivered($packageId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("UPDATE packages SET status = 'delivered', actual_delivery = NOW() WHERE id = ?");
        return $stmt->execute([$packageId]);
    } catch (PDOException $e) {
        error_log("Error en entrega: " . $e->getMessage());
        return false;
    }
}

/**
 * Genera un código de recogida aleatorio de 4 dígitos.
 */
function generatePickupCode() {
    return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Actualiza el estado del paquete a 'ready_for_pickup' y asigna el código de recogida.
 */
function setPackageReadyForPickup($packageId, $pickupCode) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE packages SET status = 'ready_for_pickup', pickup_code = ? WHERE id = ?");
        $stmt->execute([$pickupCode, $packageId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error al actualizar el estado del paquete: " . $e->getMessage());
        return false;
    }
}
?>