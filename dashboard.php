<?php
session_start();
$pageTitle = "Dashboard";
require 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$packages = getUserPackages($userId);

if (empty($packages)) {
    echo "<p>No tienes paquetes registrados.</p>";
} else {
    foreach ($packages as $package) {
        echo "<p>Paquete: {$package['tracking_number']} - Estado: {$package['status']}</p>";
        // Verifica que user_id coincide con el usuario logueado
        if ($package['user_id'] != $_SESSION['user_id']) {
            error_log("Error: Paquete no pertenece al usuario");
        }
    }
}

include 'includes/header.php';
?>

<div class="dashboard-container">
    <!-- Menú de navegación -->
    <nav class="dashboard-nav">
        <ul>
            <li class="active"><a href="dashboard.php">Panel</a></li>
            <li><a href="tracking_form.php">Seguimiento</a></li>
            <li><a href="pickup.php">Recogida</a></li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'packages.php' ? 'active' : ''; ?>"><a href="packages.php">Mis Paquetes</a></li>
        </ul>
    </nav>

    <div class="welcome-section">
        <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <p>Gestiona tus entregas desde el panel de control</p>
    </div>

    <!-- Tarjetas de funcionalidades -->
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <div class="card-icon">
                <i class="fas fa-map-marked-alt"></i>
            </div>
            <h3>Seguimiento en tiempo Real</h3>
            <p>Observa la ubicación exacta de tus paquetes mientras están en camino.</p>
            <a href="tracking.php" class="btn btn-card">Ver seguimiento</a>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <i class="fas fa-qrcode"></i>
            </div>
            <h3>Recogida de paquetes</h3>
            <p>Recoge tus paquetes de forma segura con tu código único de 4 dígitos.</p>
            <a href="pickup.php" class="btn btn-card">Recoger paquete</a>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <i class="fas fa-box-open"></i>
            </div>
            <h3>Tus paquetes</h3>
            <p>Consulta el estado de todos tus envíos actuales e históricos.</p>
            <a href="packages.php" class="btn btn-card">Ver paquetes</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>