<?php
session_start();
include 'includes/auth.php';

// Verificar que el usuario viene del proceso de registro y está logueado
if (!isset($_SESSION['just_registered']) || !isset($_SESSION['user_id'])) {
    header('Location: signup.php');
    exit;
}

// Limpiar la bandera de sesión
unset($_SESSION['just_registered']);

$pageTitle = "Registro Exitoso";
include 'includes/header.php';
?>

<div class="confirmation-container">
    <div class="confirmation-icon">✓</div>
    <h1>¡Registro Completado con Éxito!</h1>
    
    <div class="confirmation-message">
        <p>Bienvenido/a a <strong>DroneSafe</strong>, <?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></p>
        <p>Tu cuenta ha sido creada correctamente.</p>
    </div>
    
    <div class="confirmation-actions">
        <a href="dashboard.php" class="btn btn-primary">Ir a mi Panel</a>
        <a href="index.php" class="btn btn-outline">Volver al Inicio</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>