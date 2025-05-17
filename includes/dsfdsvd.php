<?php
require 'config.php';

/**
 * Registra un nuevo usuario en la base de datos
 */
function registerUser($username, $email, $password) {
    global $pdo;
    
    try {
        // Validaciones previas
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if (strlen($username) < 4) {
            throw new Exception("El usuario debe tener al menos 4 caracteres");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Formato de email inválido");
        }

        if (strlen($password) < 8) {
            throw new Exception("La contraseña debe tener mínimo 8 caracteres");
        }

        // Verificar si el usuario/email ya existen
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("El usuario o email ya están registrados");
        }

        // Hash seguro de la contraseña
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Insertar nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);
        
        return $pdo->lastInsertId();

    } catch (PDOException $e) {
        error_log("Error de base de datos: " . $e->getMessage());
        throw new Exception("Error al procesar el registro");
    } catch (Exception $e) {
        error_log("Error de validación: " . $e->getMessage());
        throw $e; // Re-lanzamos para manejar en el controlador
    }
}

/**
 * Autentica a un usuario
 */
function loginUser($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
        
        return false;

    } catch (PDOException $e) {
        error_log("Error de login: " . $e->getMessage());
        throw new Exception("Error al iniciar sesión");
    }
}

/**
 * Verifica si hay una sesión activa
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Obtiene los paquetes de un usuario
 */
function getUserPackages($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM packages WHERE user_id = ? ORDER BY estimated_delivery DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener paquetes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene la ubicación de un dron
 */
function getDroneLocation($droneId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM drone_locations WHERE drone_id = ? ORDER BY timestamp DESC LIMIT 1");
        $stmt->execute([$droneId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener ubicación: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica un código de recogida
 */
function verifyPickupCode($packageId, $code) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND pickup_code = ? LIMIT 1");
        $stmt->execute([$packageId, $code]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error en verificación: " . $e->getMessage());
        return false;
    }
}

/**
 * Marca un paquete como entregado
 */
function markAsDelivered($packageId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE packages SET status = 'delivered', actual_delivery = NOW() WHERE id = ?");
        return $stmt->execute([$packageId]);
    } catch (PDOException $e) {
        error_log("Error en entrega: " . $e->getMessage());
        return false;
    }
}

/**
 * Genera un código de recogida aleatorio de 4 dígitos.
 */
function generatePickupCode() {
    return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Actualiza el estado del paquete a 'ready_for_pickup' y asigna el código de recogida.
 */
function setPackageReadyForPickup($packageId, $pickupCode) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE packages SET status = 'ready_for_pickup', pickup_code = ? WHERE id = ?");
        $stmt->execute([$pickupCode, $packageId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error al actualizar el estado del paquete: " . $e->getMessage());
        return false;
    }
}
?>




<?php
session_start();
require 'includes/auth.php';

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
            if (registerUser($username, $email, $password)) {
                $_SESSION['just_registered'] = true;
                $_SESSION['username'] = $username;
                header('Location: registration_success.php');
                exit;
            } else {
                $error = "El usuario o email ya están registrados";
            }
        } catch (Exception $e) {
            $error = "Error en el registro: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<!-- Tu formulario HTML actual (se mantiene igual) -->
<div class="auth-container">
    <h2>Crear Cuenta</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form action="signup.php" method="POST">
        <!-- Campos del formulario -->
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



<?php
/* session_start();
require 'includes/db.php';
require 'includes/auth.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'error' => 'No autorizado']));
}

if (!isset($_GET['package_id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'error' => 'ID de paquete no proporcionado']));
}

$packageId = $_GET['package_id'];
$userId = $_SESSION['user_id'];

// Verificar que el paquete pertenece al usuario
$stmt = $pdo->prepare("SELECT id FROM packages WHERE id = ? AND user_id = ?");
$stmt->execute([$packageId, $userId]);
$package = $stmt->fetch();

if (!$package) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'error' => 'Paquete no encontrado']));
}

// Generar código de 4 dígitos
$code = sprintf('%04d', rand(0, 9999));

// Actualizar el paquete en la base de datos
try {
    $updateStmt = $pdo->prepare("UPDATE packages 
                                SET status = 'ready_for_pickup', 
                                    pickup_code = ?,
                                    code_generated_at = NOW()
                                WHERE id = ?");
    $updateStmt->execute([$code, $packageId]);
    
    echo json_encode(['success' => true, 'code' => $code]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit(json_encode(['success' => false, 'error' => 'Error al actualizar la base de datos']));
} */

/*
session_start();
require 'includes/db.php';
require 'includes/auth.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'error' => 'No autorizado']));
}

if (!isset($_GET['package_id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'error' => 'ID de paquete no proporcionado']));
}

$packageId = $_GET['package_id'];
$userId = $_SESSION['user_id'];

// Verificar que el paquete pertenece al usuario y está en tránsito
$stmt = $pdo->prepare("SELECT id, status FROM packages WHERE id = ? AND user_id = ? AND status = 'in_transit'");
$stmt->execute([$packageId, $userId]);
$package = $stmt->fetch();

if (!$package) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'error' => 'Paquete no encontrado o no está en tránsito']));
}

// Generar código de 4 dígitos
$code = sprintf('%04d', rand(0, 9999));

// Actualizar el paquete en la base de datos
try {
    $updateStmt = $pdo->prepare("UPDATE packages 
                                SET status = 'ready_for_pickup', 
                                    pickup_code = ?,
                                    code_generated_at = NOW()
                                WHERE id = ?");
    $updateStmt->execute([$code, $packageId]);

    echo json_encode(['success' => true, 'code' => $code]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit(json_encode(['success' => false, 'error' => 'Error al actualizar la base de datos']));
}*/

/*
session_start();
require 'includes/auth.php';
require 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Verificar que se ha proporcionado un ID de seguimiento
if (!isset($_GET['tracking_id'])) {
    header('Location: tracking_form.php');
    exit;
}

$packageId = $_GET['tracking_id'];
$userId = $_SESSION['user_id'];

// Obtener el paquete específico
$stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND user_id = ?");
$stmt->execute([$packageId, $userId]);
$package = $stmt->fetch();

if (!$package) {
    header('Location: tracking_form.php?error=invalid_id');
    exit;
}

// Coordenadas exactas
$aldiLat = 38.0211;
$aldiLon = -4.0333;
$sagradaLat = 38.0216;
$sagradaLon = -4.0246;

// Ruta específica en Andújar (Jaén)
$droneData = [
    'current_location' => [
        'latitude' => $aldiLat,
        'longitude' => $aldiLon
    ],
    'route' => [
        [
            'latitude' => $aldiLat,
            'longitude' => $aldiLon,
            'name' => 'Supermercado ALDI'
        ],
        [
            'latitude' => 38.03850,
            'longitude' => -4.05500,
            'name' => 'Av. Doctor Fleming'
        ],
        [
            'latitude' => 38.03820,
            'longitude' => -4.05380,
            'name' => 'Calle Las Monjas'
        ],
        [
            'latitude' => $sagradaLat,
            'longitude' => $sagradaLon,
            'name' => 'Escuelas Profesionales Sagrada Familia'
        ]
    ],
    'speed' => 30, // km/h
    'distance_remaining' => 0.8, // km
    'estimated_time' => 2, // minutos
    'pickup_code' => $package['pickup_code'],
    'package_id' => $package['id']
];

$pageTitle = "Seguimiento de Paquete #" . htmlspecialchars($package['tracking_number']);
include 'includes/header.php';
?>

<div class="tracking-container">
    <h1>Seguimiento del Paquete #<?php echo htmlspecialchars($package['tracking_number']); ?></h1>

    <div class="tracking-info">
        <p><strong>Estado:</strong> <span class="status-badge status-<?php echo htmlspecialchars($package['status']); ?>">
            <?php
            switch ($package['status']) {
                case 'in_transit':
                    echo 'En ruta';
                    break;
                case 'ready_for_pickup':
                    echo 'Listo para recoger';
                    break;
                case 'delivered':
                    echo 'Entregado';
                    break;
                default:
                    echo 'Desconocido';
            }
            ?>
            </span>
        </p>
        <p><strong>Ubicación de recogida:</strong> <?php echo htmlspecialchars($package['pickup_location']); ?></p>
        <p><strong>Código de recogida:</strong> <span id="pickup-code-display">
            <?php echo htmlspecialchars($package['pickup_code'] ?? 'Pendiente'); ?></span>
            <?php if ($package['status'] === 'in_transit'): ?>
                <button id="generate-code-btn" onclick="generatePickupCode()">Generar Código</button>
            <?php endif; ?>
        </p>
    </div>

    <div id="tracking-map"></div>

    <div class="simulation-options">
        <button id="start-simulation">Iniciar Simulación</button>
        <label for="simulation-speed">Velocidad:</label>
        <input type="number" id="simulation-speed" value="30" min="10" max="100"> km/h
    </div>
</div>

<script>
    const droneData = <?php echo json_encode($droneData); ?>;
    const routeCoordinates = droneData.route.map(coord => ({
        latitude: coord.latitude,
        longitude: coord.longitude,
        name: coord.name
    }));

    let map;
    let polyline;
    let marker;
    let nextIndex = 1;
    let animation;
    let currentSpeed = droneData.speed; // Velocidad inicial

    function initMap() {
        map = L.map('tracking-map').setView([droneData.current_location.latitude, droneData.current_location.longitude], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        // Crear la polilínea de la ruta
        const latlngs = routeCoordinates.map(coord => [coord.latitude, coord.longitude]);
        polyline = L.polyline(latlngs, { color: 'blue' }).addTo(map);

        // Crear el marcador del dron
        marker = L.marker([droneData.current_location.latitude, droneData.current_location.longitude]).addTo(map);
    }

    function moveDrone() {
        if (nextIndex < routeCoordinates.length) {
            const nextPoint = routeCoordinates[nextIndex];
            const currentLatLng = marker.getLatLng();
            const destLatLng = L.latLng(nextPoint.latitude, nextPoint.longitude);

            const distance = currentLatLng.distanceTo(destLatLng); // Distancia en metros
            const duration = (distance / (currentSpeed * 1000 / 3600)) * 1000; // Tiempo en milisegundos

            animation = L.Marker.movingMarker([currentLatLng, destLatLng], duration, { autostart: true }).addTo(map);
            marker.remove();
            marker = animation;

            animation.on('end', () => {
                nextIndex++;
                moveDrone();
                updateMapCenter();
                if (nextIndex >= routeCoordinates.length) {
                    generatePickupCode();
                }
            });
        }
    }

    function updateMapCenter() {
        map.panTo(marker.getLatLng());
    }

    document.getElementById('start-simulation').addEventListener('click', () => {
        initMap();
        moveDrone();
        document.getElementById('start-simulation').disabled = true;
    });

    document.getElementById('simulation-speed').addEventListener('change', function() {
        currentSpeed = parseInt(this.value, 10);
        if (animation) {
            animation.pause();
            animation.start();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar el mapa si la simulación ya está en curso
        if (nextIndex > 1) {
            initMap();
        }
    });

    function generatePickupCode() {
        fetch('generate_code.php?package_id=<?php echo $package['id']; ?>')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Actualizar UI
                    document.getElementById('pickup-code-display').textContent = data.code;
                    updateStatus('ready_for_pickup');

                    // Mostrar notificación
                    alert('¡Tu paquete ha llegado! Código de recogida: ' + data.code);

                    // Recargar la página después de 5 segundos para actualizar todos los datos
                    setTimeout(() => location.reload(), 5000);
                } else {
                    console.error('Error al generar código:', data.error);
                    alert('Error al generar el código de recogida: ' + data.error + '. Por favor recarga la página.');
                }
            })
            .catch(error => {
                console.error('Error al generar código:', error);
                alert('Error al generar el código de recogida. Por favor recarga la página.');
            });
    }

    function updateStatus(newStatus) {
        document.body.dataset.packageStatus = newStatus;
        document.querySelectorAll('.step').forEach(step =>
            step.classList.toggle('completed', step.dataset.status === 'completed' ||
                                            step.dataset.status === 'active' && newStatus !== 'in_transit'));
        document.querySelectorAll('.step-line').forEach(line =>
            line.classList.toggle('completed', line.dataset.status === 'completed' ||
                                            line.dataset.status === 'active' && newStatus !== 'in_transit'));
        document.querySelectorAll('.status-indicator div').forEach(div =>
            div.classList.toggle('completed', div.dataset.status === 'completed' ||
                                           div.dataset.status === 'active' && newStatus !== 'in_transit'));
        document.querySelector('.status-indicator span').textContent =
            newStatus == 'ready_for_pickup' ? 'Listo para recoger' : 'En ruta';

        document.querySelector('.status-badge').textContent =
            newStatus == 'ready_for_pickup' ? 'Listo para recoger' : 'En ruta';
        document.querySelector('.status-badge').className =
            'status-badge status-' + newStatus;
    }
</script>

<?php include 'includes/footer.php'; ?>

?> */

/*
session_start();
require 'includes/db.php';
require 'includes/auth.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'error' => 'No autorizado']));
}

if (!isset($_GET['package_id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'error' => 'ID de paquete no proporcionado']));
}

$packageId = $_GET['package_id'];
$userId = $_SESSION['user_id'];

// Verificar que el paquete pertenece al usuario y está en tránsito
$stmt = $pdo->prepare("SELECT id, status FROM packages WHERE id = ? AND user_id = ? AND status = 'in_transit'");
$stmt->execute([$packageId, $userId]);
$package = $stmt->fetch();

if (!$package) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'error' => 'Paquete no encontrado o no está en tránsito']));
}

// Generar código de 4 dígitos
$code = sprintf('%04d', rand(0, 9999));

// Actualizar el paquete en la base de datos
try {
    $updateStmt = $pdo->prepare("UPDATE packages 
                                SET status = 'ready_for_pickup', 
                                    pickup_code = ?,
                                    actual_delivery = NOW()
                                WHERE id = ?");
    $updateStmt->execute([$code, $packageId]);

    echo json_encode(['success' => true, 'code' => $code]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit(json_encode(['success' => false, 'error' => 'Error al actualizar la base de datos']));
}

*/
<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'error' => 'No autorizado']));
}

if (!isset($_GET['package_id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'error' => 'ID de paquete no proporcionado']));
}

$packageId = $_GET['package_id'];
$userId = $_SESSION['user_id'];

// Verificar que el paquete pertenece al usuario y está en tránsito
$stmt = $pdo->prepare("SELECT id, status FROM packages WHERE id = ? AND user_id = ? AND status = 'in_transit'");
$stmt->execute([$packageId, $userId]);
$package = $stmt->fetch();

if (!$package) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'error' => 'Paquete no encontrado o no está en tránsito']));
}

// Generar código de 4 dígitos
$code = sprintf('%04d', rand(0, 9999));

// Actualizar el paquete en la base de datos
try {
    $updateStmt = $pdo->prepare("UPDATE packages 
                                SET status = 'ready_for_pickup', 
                                    pickup_code = ?,
                                    actual_delivery = NOW()
                                WHERE id = ?");
    $updateStmt->execute([$code, $packageId]);

    echo json_encode(['success' => true, 'code' => $code]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit(json_encode(['success' => false, 'error' => 'Error al actualizar la base de datos']));
}