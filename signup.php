<?php
session_start();
$pageTitle = "Registro";
$bodyClass = "auth-page";
include 'includes/header.php';
include 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validaciones
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios';
    } 
    elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } 
    elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato de email inválido";
    } 
    else {
        // Intentar registro
        try {
            $userId = registerUser($username, $email, $password);
            if ($userId) {
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['just_registered'] = true; // <-- Añadir esta línea
                header('Location: registration_success.php'); // <-- Cambiar esta línea
                exit;
            } else {
                $error = "El usuario o email ya están registrados";
            }
        } catch (Exception $e) {
            $error = "Error en el registro: " . $e->getMessage();
        }
    }
}

?>


<div class="auth-container">
    <h2>Crear Cuenta</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form action="signup.php" method="POST">
        <div class="form-group">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" required 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña (mínimo 8 caracteres)</label>
            <input type="password" id="password" name="password" required minlength="8">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmar contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
        </div>
        
        <button type="submit" class="btn btn-block">Registrarse</button>
    </form>
    
    <div class="auth-footer">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>