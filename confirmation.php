<?php
session_start();
require 'includes/auth.php';
require 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['package_id'])) {
    header('Location: dashboard.php');
    exit;
}

$packageId = $_GET['package_id'];

// Obtener información del paquete
$stmt = $pdo->prepare("SELECT tracking_number, pickup_location, actual_delivery FROM packages WHERE id = ? AND user_id = ?");
$stmt->execute([$packageId, $_SESSION['user_id']]);
$package = $stmt->fetch();

if (!$package) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Confirmación de Recogida";
include 'includes/header.php';
?>

<div class="confirmation-container">
    <div class="confirmation-icon">
        <i class="fas fa-check-circle"></i>
    </div>
    <h1>¡Recogida Confirmada!</h1>
    <p>El paquete con número de seguimiento <strong><?php echo htmlspecialchars($package['tracking_number']); ?></strong> ha sido recogido exitosamente.</p>
    <p><strong>Ubicación de recogida:</strong> <?php echo htmlspecialchars($package['pickup_location']); ?></p>
    <p><strong>Fecha y hora de recogida:</strong> <?php echo htmlspecialchars($package['actual_delivery']); ?></p>
    <div class="confirmation-actions">
        <a href="dashboard.php" class="btn">Volver al Panel</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>