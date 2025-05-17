<?php
session_start();
require 'includes/auth.php';
require 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Introducir ID de Seguimiento";
include 'includes/header.php';

$error = null;
$deliveredPackage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trackingNumber = trim($_POST['tracking_number']);
    
    // Verificar el paquete
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE tracking_number = ? AND user_id = ?");
    $stmt->execute([$trackingNumber, $_SESSION['user_id']]);
    $package = $stmt->fetch();
    
    if ($package) {
        if ($package['status'] === 'delivered') {
            $deliveredPackage = [
                'tracking_number' => $package['tracking_number'],
                'delivery_date' => date('d/m/Y H:i', strtotime($package['actual_delivery'])),
                'pickup_location' => $package['pickup_location']
            ];
        } else {
            header('Location: tracking.php?tracking_id=' . $package['id']);
            exit;
        }
    } else {
        $error = "ID de seguimiento no válido o no pertenece a tu cuenta";
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

<div class="auth-container">
    <h1 class="auth-title">Seguimiento de Paquete</h1>
   
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="tracking_number">Introduce el número de seguimiento de tu paquete:</label>
            <input type="text" id="tracking_number" name="tracking_number" required 
                   placeholder="Ej: DS123456789" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Ver Seguimiento</button>
    </form>
    
    <div class="auth-footer">
        <p>¿No tienes un número de seguimiento? <a href="dashboard.php">Volver al panel</a></p>
    </div>
</div>

<!-- Modal para paquetes ya entregados -->
<?php if ($deliveredPackage): ?>
<div id="deliveredModal" class="modal" style="display: block;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div class="modal-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>Este paquete ya se ha recogido</h3>
        <div class="modal-info">
            <p><strong>Número de Seguimiento:</strong> <?php echo htmlspecialchars($deliveredPackage['tracking_number']); ?></p>
            <p><strong>Fecha de Recogida:</strong> <?php echo $deliveredPackage['delivery_date']; ?></p>
            <p><strong>Origen:</strong> <?php echo htmlspecialchars($deliveredPackage['pickup_location']); ?></p>
        </div>
        <div class="modal-actions">
            <button id="closeModalBtn" class="btn">Cerrar</button>
            <a href="packages.php" class="btn btn-outline">Ver Historial</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('deliveredModal');
    const closeBtn = document.getElementById('closeModalBtn');
    
    if (modal) {
        // Función para cerrar el modal
        function closeModal() {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300); // Coincide con la duración de la transición
        }
        
        // Event listeners
        document.querySelector('.close-modal').addEventListener('click', closeModal);
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        // Mostrar el modal con transición
        setTimeout(() => {
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }, 10);
    }
});
</script>
<?php include 'includes/footer.php'; ?>