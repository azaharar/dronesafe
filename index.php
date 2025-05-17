<?php 
$pageTitle = "Inicio";
include 'includes/header.php'; 
?>

<section class="hero home-hero">
    <div class="hero-content container">
        <h2>Entregas seguras para la Era Moderna</h2>
        <p class="hero-text">Experimenta entregas de paquetes con drones de forma rápida, segura y con seguimiento en tiempo real.</p>
        <a href="signup.php" class="btn btn-hero">Comenzar</a>
    </div>
</section>

<section class="features-section">
    <div class="features-container">
        <h2 class="section-title">Nuestras Ventajas</h2>
        <div class="features">
            <div class="feature">
                <h3>Rápido como un rayo</h3>
                <p>Recibe tus paquetes en tiempo récord con nuestra flota de drones autónomos.</p>
            </div>
            <div class="feature">
                <h3>Recogida Segura</h3>
                <p>Códigos exclusivos de 4 dígitos garantizan que solo tú puedas acceder a tus entregas.</p>
            </div>
            <div class="feature">
                <h3>Rastreo en tiempo real</h3>
                <p>Observa el recorrido de tu paquete desde el almacén hasta tu puerta.</p>
            </div>
        </div>
    </div>
</section>

<section class="container how-it-works">
    <h2 class="section-title">¿Cómo funciona DroneSafe?</h2>
    <div class="steps">
        <div class="step">
            <span class="step-number">1</span>
            <p class="step-text">Realiza tu pedido con un comercio asociado</p>
        </div>
        <div class="step">
            <span class="step-number">2</span>
            <p class="step-text">Sigue el trayecto de tu dron en tiempo real</p>
        </div>
        <div class="step">
            <span class="step-number">3</span>
            <p class="step-text">Ingresa el código para recoger tu paquete de forma segura</p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>