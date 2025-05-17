<?php
session_start();
require 'includes/auth.php';  // MOVER ESTA LÍNEA ARRIBA DE TODO

$pageTitle = "Iniciar sesión";
$bodyClass = "auth-page";
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (loginUser($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>

<div class="auth-container">
    <h2 class="auth-title">Iniciar sesión</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-block">Iniciar sesión</button>
    </form>
    
    <div class="auth-footer">
        ¿No tienes cuenta? <a href="signup.php" class="auth-link">Regístrate aquí</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>