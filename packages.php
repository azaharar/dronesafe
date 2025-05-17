<?php
session_start();
require 'includes/auth.php';
require 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Mis Paquetes";
include 'includes/header.php';

// Obtener los paquetes del usuario
$packages = getUserPackages($_SESSION['user_id']);

// Filtrar paquetes listos para recoger
$readyForPickup = array_filter($packages, function($package) {
    return $package['status'] === 'ready_for_pickup';
});

// Filtrar paquetes entregados
$delivered = array_filter($packages, function($package) {
    return $package['status'] === 'delivered';
});

// Asegurarse de que los datos se actualicen dinámicamente
foreach ($packages as &$package) {
    if ($package['status'] === 'ready_for_pickup' && strtotime($package['estimated_delivery']) < time()) {
        $package['status'] = 'delivered';
        $stmt = $pdo->prepare("UPDATE packages SET status = 'delivered', actual_delivery = NOW() WHERE id = ?");
        $stmt->execute([$package['id']]);
    }
}
?>

<div class="dashboard-container">
    <nav class="dashboard-nav">
        <ul>
            <li><a href="dashboard.php">Panel</a></li>
            <li><a href="tracking.php">Seguimiento</a></li>
            <li><a href="pickup.php">Recogida</a></li>
            <li class="active"><a href="packages.php">Mis Paquetes</a></li>
        </ul>
    </nav>

    <div class="packages-container">
        <h1>Mis Paquetes</h1>

        <h2>Listos para Recoger</h2>
        <?php if (empty($readyForPickup)): ?>
            <p>No tienes paquetes listos para recoger</p>
        <?php else: ?>
            <ul>
                <?php foreach ($readyForPickup as $package): ?>
                    <li>
                        <strong>Número de seguimiento:</strong> <?php echo htmlspecialchars($package['tracking_number']); ?><br>
                        <strong>Ubicación:</strong> <?php echo htmlspecialchars($package['pickup_location']); ?><br>
                        <strong>Estado:</strong>Entregado
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2>Entregados</h2>
        <?php if (empty($delivered)): ?>
            <p>No tienes paquetes entregados.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($delivered as $package): ?>
                    <li>
                        <strong>Número de seguimiento:</strong> <?php echo htmlspecialchars($package['tracking_number']); ?><br>
                        <strong>Origen:</strong> <?php echo htmlspecialchars($package['pickup_location']); ?><br>
                        <strong>Estado:</strong> Entregado
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>