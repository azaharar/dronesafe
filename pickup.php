<?php
session_start();
require 'includes/auth.php';
require 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);

    if (preg_match('/^\d{4}$/', $code)) {
        $stmt = $pdo->prepare("SELECT id, tracking_number FROM packages 
                              WHERE pickup_code = ? 
                              AND user_id = ? 
                              AND status = 'ready_for_pickup'");
        $stmt->execute([$code, $_SESSION['user_id']]);
        $package = $stmt->fetch();

        if ($package) {
            // Actualizar el estado del paquete a 'delivered'
            $updateStmt = $pdo->prepare("UPDATE packages 
                                        SET status = 'delivered', 
                                            actual_delivery = NOW() 
                                        WHERE id = ?");
            $updateStmt->execute([$package['id']]);

            if ($updateStmt->rowCount() > 0) {
                // Redirigir a la página de confirmación con el ID del paquete
                header('Location: pickup_confirmation.php?package_id=' . $package['id']);
                exit;
            } else {
                $error = "Error al actualizar el estado del paquete";
            }
        } else {
            $error = "Código incorrecto o paquete no disponible para recogida";
        }
    } else {
        $error = "Por favor ingresa un código válido de 4 dígitos";
    }
}

$pageTitle = "Recogida de Paquetes";
include 'includes/header.php';
?>

<div class="dashboard-container">
    <nav class="dashboard-nav">
        <ul>
            <li><a href="dashboard.php">Panel</a></li>
            <li><a href="tracking.php">Seguimiento</a></li>
            <li class="active"><a href="pickup.php">Recogida</a></li>
            <li><a href="packages.php">Mis Paquetes</a></li>
        </ul>
    </nav>

    <div class="pickup-container">
        <h1>Recoger Paquete</h1>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="code">Ingresa tu código de 4 dígitos:</label>
                <input type="text" id="code" name="code" maxlength="4" pattern="\d{4}" required>
            </div>
            <button type="submit" class="btn">Recoger Paquete</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>