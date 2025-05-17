<?php
/* session_start();
require 'includes/auth.php';
require 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = null;
$success = false;
$deliveryMessage = null; // Inicializar la variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);

    if (isset($_GET['package_id'])) {
        $packageId = (int) $_GET['package_id'];

        // Verificar si el paquete ya fue entregado
        $stmt = $pdo->prepare("SELECT status, actual_delivery FROM packages WHERE id = ? AND user_id = ?");
        $stmt->execute([$packageId, $_SESSION['user_id']]);
        $package = $stmt->fetch();

        if ($package && $package['status'] === 'delivered') {
            $deliveryMessage = "Este paquete ya fue recogido el " .
                date('d/m/Y H:i', strtotime($package['actual_delivery']));
        } else {
            // Verificar el PIN y marcar como entregado
            if (preg_match('/^\d{4}$/', $code)) {
                $stmt = $pdo->prepare("SELECT id FROM packages 
                                      WHERE pickup_code = ? 
                                      AND user_id = ? 
                                      AND status = 'ready_for_pickup'
                                      AND id = ?"); // Añadir la condición del ID
                $stmt->execute([$code, $_SESSION['user_id'], $packageId]);
                $package = $stmt->fetch();

                if ($package) {
                    $updateStmt = $pdo->prepare("UPDATE packages 
                                                SET status = 'delivered', 
                                                    actual_delivery = NOW() 
                                                WHERE id = ? AND status = 'ready_for_pickup'");
                    $updateStmt->execute([$package['id']]);

                    if ($updateStmt->rowCount() > 0) {
                        error_log("Paquete actualizado a 'delivered': ID " . $package['id']);
                        header('Location: pickup_confirmation.php?package_id=' . $package['id']);
                        exit;
                    } else {
                        error_log("Error al actualizar el paquete: ID " . $package['id']);
                        $error = "No se pudo actualizar el estado del paquete.";
                    }
                } else {
                    error_log("Código incorrecto o paquete no disponible para recogida: Código ingresado: $code, Usuario: " . $_SESSION['user_id'] . ", Paquete: " . $packageId);
                    $error = "Código incorrecto o paquete no disponible para recogida.";
                }
            } else {
                $error = "Por favor ingresa un código válido de 4 dígitos.";
            }
        }
    } else {
        $error = "ID de paquete no proporcionado.";
    }
}

$pageTitle = "Recogida de Paquetes";
include 'includes/header.php';
?>

<div class="dashboard-container">
    <nav class="dashboard-nav">
        <ul>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><a href="dashboard.php">Panel</a></li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'tracking.php' ? 'active' : ''; ?>"><a href="tracking.php">Seguimiento</a></li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'pickup.php' ? 'active' : ''; ?>"><a href="pickup.php">Recogida</a></li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'packages.php' ? 'active' : ''; ?>"><a href="packages.php">Mis Paquetes</a></li>
        </ul>
    </nav>

    <div class="pickup-container">
        <h1>Recoger mi Paquete</h1>
        <?php if ($deliveryMessage): ?>
            <div class="alert alert-info">
                <i class="fas fa-check-circle"></i> <?php echo $deliveryMessage; ?>
                <p>No es necesario volver a recogerlo.</p>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($deliveryMessage)): ?>
            <form id="pickup-form" action="pickup.php?package_id=<?php echo isset($_GET['package_id']) ? htmlspecialchars($_GET['package_id']) : ''; ?>" method="POST">
                <div class="form-group">
                    <label for="pickup-code">Inserta el código de 4 dígitos</label>
                    <input type="text" id="pickup-code" name="code" maxlength="4" pattern="\d{4}" required>
                </div>
                <button type="submit" class="btn btn-primary">Verificar Código</button>
            </form>
        <?php endif; ?>

        <?php
        $stmt = $pdo->prepare("SELECT id, tracking_number, pickup_location, status FROM packages WHERE user_id = ? AND status = 'ready_for_pickup'");
        $stmt->execute([$_SESSION['user_id']]);
        $packages = $stmt->fetchAll();

        foreach ($packages as $package): ?>
            <div class="package-pickup">
                <h3>Paquete #<?php echo htmlspecialchars($package['tracking_number']); ?></h3>
                <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($package['pickup_location']); ?></p>
                <form action="pickup.php?package_id=<?php echo $package['id']; ?>" method="POST">
                    <label for="pickup-code-<?php echo $package['id']; ?>">Código de 4 dígitos</label>
                    <input type="text" id="pickup-code-<?php echo $package['id']; ?>" name="code" maxlength="4" pattern="\d{4}" required>
                    <button type="submit" class="btn">Recoger Paquete</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?> */


session_start();
require 'includes/auth.php';
require 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Verificar que se proporcionó un ID de paquete
if (!isset($_GET['package_id'])) {
    header('Location: pickup.php');
    exit;
}

$packageId = (int)$_GET['package_id'];

// Obtener información del paquete
$stmt = $pdo->prepare("SELECT tracking_number, actual_delivery, pickup_location 
                       FROM packages 
                       WHERE id = ? AND user_id = ? AND status = 'delivered'");
$stmt->execute([$packageId, $_SESSION['user_id']]);
$package = $stmt->fetch();

if (!$package) {
    header('Location: pickup.php');
    exit;
}

$pageTitle = "Recogida Confirmada";
include 'includes/header.php';
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

    <div class="confirmation-container">
        <div class="confirmation-icon">✓</div>
        <h1>¡Recogida Confirmada!</h1>
        
        <div class="confirmation-message">
            <p>Has recogido correctamente el paquete <strong>#<?= htmlspecialchars($package['tracking_number']) ?></strong></p>
            <p><strong>Ubicación:</strong> <?= htmlspecialchars($package['pickup_location']) ?></p>
            <p><strong>Fecha/Hora:</strong> <?= date('d/m/Y H:i', strtotime($package['actual_delivery'])) ?></p>
        </div>
        
        <div class="confirmation-actions">
            <a href="packages.php" class="btn btn-primary">Ver mis Paquetes</a>
            <a href="dashboard.php" class="btn btn-outline">Volver al Panel</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>