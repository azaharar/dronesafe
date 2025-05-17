<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DroneSafe - <?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="/dronesafe/includes/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2F9C95">
    <link rel="manifest" href="/manifest.json">
    <!-- Para iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/img/icon-192x192.png">
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    <header>
        <div class="container header-container">
            <div class="logo">
                <h1>DroneSafe</h1>
                <p>Entrega segura con drones</p>
            </div>
            <nav>
                <ul class="nav-list">
                    <li class="nav-item"><a href="index.php" class="nav-link">Inicio</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a href="dashboard.php" class="nav-link">Panel</a></li>
                        <li class="nav-item"><a href="logout.php" class="nav-link">Salir</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a href="login.php" class="nav-link">Iniciar sesión</a></li>
                        <li class="nav-item"><a href="signup.php" class="nav-link">Registro</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>