<?php
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

// Después de obtener el paquete:
if ($package['status'] === 'delivered') {
    $deliveryMessage = "Este paquete fue recogido el " . 
        date('d/m/Y H:i', strtotime($package['actual_delivery']));
}

$pageTitle = "Seguimiento de Paquete #" . htmlspecialchars($package['tracking_number']);
include 'includes/header.php';


$pageTitle = "Seguimiento de Drones";
include 'includes/header.php';

// Obtener los paquetes del usuario
$packages = getUserPackages($_SESSION['user_id']);

// Convertir coordenadas DMS a decimal
function dmsToDecimal($degrees, $minutes, $seconds, $direction) {
    $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
    if ($direction == 'S' || $direction == 'W') {
        $decimal *= -1;
    }
    return $decimal;
}

// Coordenadas exactas
$aldiLat = dmsToDecimal(38, 2, 11, 'N');    // 38°02'11"N
$aldiLon = dmsToDecimal(4, 3, 33, 'W');     // 4°03'33"W
$sagradaLat = dmsToDecimal(38, 2, 16, 'N'); // 38°02'16"N
$sagradaLon = dmsToDecimal(4, 2, 46, 'W');  // 4°02'46"W


// Si no hay paquetes, crear uno demo
if (empty($packages)) {
    $trackingNumber = generateUniqueTrackingNumber($pdo);
    $userId = $_SESSION['user_id'];
    $droneId = 'DRN' . rand(1000, 9999);
    $pickupCode = sprintf('%04d', rand(0, 9999));

    $stmt = $pdo->prepare("INSERT INTO packages (tracking_number, user_id, drone_id, pickup_code, status, estimated_delivery, pickup_location) 
                          VALUES (?, ?, ?, ?, 'in_transit', DATE_ADD(NOW(), INTERVAL 30 MINUTE), 'Supermercado ALDI, Andújar')");
    $stmt->execute([$trackingNumber, $userId, $droneId, $pickupCode]);

    $packages = getUserPackages($userId);
}

$package = $packages[0]; // Tomamos el primer paquete

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

// Generar puntos intermedios aleatorios entre el origen y el destino
function generateRandomMiddlePoints($originLat, $originLon, $destLat, $destLon, $numPoints = 3) {
    $points = [];
    for ($i = 1; $i <= $numPoints; $i++) {
        
        $t = $i / ($numPoints + 1);

       
        $lat = $originLat + ($destLat - $originLat) * $t;
        $lon = $originLon + ($destLon - $originLon) * $t;

        $lat += (rand(-100, 100) / 100000); 
        $lon += (rand(-100, 100) / 100000); 

        $points[] = [
            'latitude' => $lat,
            'longitude' => $lon,
            'name' => "Punto intermedio $i"
        ];
    }
    return $points;
}

$middlePoints = generateRandomMiddlePoints($aldiLat, $aldiLon, $sagradaLat, $sagradaLon, 3);

// Añadir los puntos intermedios a la ruta del dron
    $droneData['route'] = array_merge(
    [
        [
            'latitude' => $aldiLat,
            'longitude' => $aldiLon,
            'name' => 'Supermercado ALDI'
        ]
    ],
    $middlePoints,
    [
        [
            'latitude' => $sagradaLat,
            'longitude' => $sagradaLon,
            'name' => 'Escuelas Profesionales Sagrada Familia'
        ]
    ]
);
?>

<div class="dashboard-container">
    <nav class="dashboard-nav">
        <ul>
            <li><a href="dashboard.php">Panel</a></li>
            <li class="active"><a href="tracking.php">Seguimiento</a></li>
            <li><a href="pickup.php">Recogida</a></li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'packages.php' ? 'active' : ''; ?>"><a href="packages.php">Mis Paquetes</a></li>
        </ul>
    </nav>

    <div class="tracking-container">
        
        <div class="tracking-grid">
            <div class="map-container">
                <div id="drone-map" class="drone-map"></div>
            </div>
            
            <div class="tracking-info">
                <div class="info-card">
                    <h3><i class="fas fa-drone-alt"></i> Estado del Dron</h3>
                    <div class="status-indicator <?php echo $package['status'] == 'ready_for_pickup' ? 'completed' : 'active'; ?>">
                        <span><?php 
                            echo $package['status'] == 'ready_for_pickup' ? 'Listo para recoger' : 'En ruta'; 
                        ?></span>
                    </div>
                    <div class="info-row">
                        <span>Velocidad:</span>
                        <strong><?php echo $droneData['speed']; ?> km/h</strong>
                    </div>
                    <div class="info-row">
                        <span>Distancia restante:</span>
                        <strong><?php echo $droneData['distance_remaining']; ?> km</strong>
                    </div>
                    <div class="info-row">
                        <span>Tiempo estimado:</span>
                        <strong><?php echo $droneData['estimated_time']; ?> minutos</strong>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-route"></i> Ruta del Dron</h3>
                    <div class="route-progress">
                        <?php foreach ($droneData['route'] as $index => $point): ?>
                            <div class="route-point <?php 
                                echo $index === 0 ? 'completed' : 
                                    ($index === 1 && $package['status'] == 'in_transit' ? 'current' : 
                                    ($package['status'] == 'ready_for_pickup' && $index === count($droneData['route']) - 1 ? 'completed' : '')); 
                            ?>">
                                <div class="point-marker"></div>
                                <div class="point-info">
                                    <span><?php echo $point['name']; ?></span>
                                    <?php if ($index === 0): ?>
                                        <small>Completado</small>
                                    <?php elseif ($index === 1 && $package['status'] == 'in_transit'): ?>
                                        <small>En progreso</small>
                                    <?php elseif ($package['status'] == 'ready_for_pickup' && $index === count($droneData['route']) - 1): ?>
                                        <small>Listo para recoger</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-box"></i> Información del Paquete</h3>
                    <div class="info-row">
                        <span>Número de seguimiento:</span>
                        <strong><?php echo htmlspecialchars($package['tracking_number']); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Estado:</span>
                        <strong class="status-badge status-<?php echo $package['status']; ?>">
                            <?php echo $package['status'] == 'delivered' ? 'Enviado' : ($package['status'] == 'in_transit' ? 'En ruta' : str_replace('_', ' ', $package['status'])); ?>
                        </strong>
                    </div>
                    <div class="info-row">
                        <span>Código de recogida:</span>
                        <strong id="pickup-code-display">
                            <?php echo $package['status'] == 'ready_for_pickup' ? htmlspecialchars($package['pickup_code']) : '••••'; ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        

        <!-- Mostrar el código de recogida si el paquete está listo para recoger -->
        <?php if ($package['status'] === 'ready_for_pickup'): ?>
            <p><strong>Código de recogida:</strong> <?php echo htmlspecialchars($package['pickup_code']); ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="tracking-container">
    <?php if (isset($deliveryMessage)): ?>
        <div class="alert alert-info" style="margin-bottom: 30px;">
            <i class="fas fa-check-circle"></i> <?php echo $deliveryMessage; ?>
            <p style="margin-top: 10px;">Puedes ver el historial completo en <a href="packages.php">tus paquetes</a>.</p>
        </div>
    <?php endif; ?>

<!-- Incluir Leaflet CSS y JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<!-- Script para el mapa -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Coordenadas iniciales
    var droneLocation = [<?php echo $droneData['current_location']['latitude']; ?>, <?php echo $droneData['current_location']['longitude']; ?>];
    
    // Crear mapa
    var map = L.map('drone-map').setView(droneLocation, 16);
    
    // Añadir capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);
    
    // Icono personalizado para el dron
    var droneIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/1828/1828884.png',
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });
    
    // Añadir marcador del dron
    var droneMarker = L.marker(droneLocation, {
        icon: droneIcon,
        title: 'Tu dron'
    }).addTo(map);
    
    // Crear ruta
    var routeCoordinates = [
        <?php foreach ($droneData['route'] as $point): ?>
            [<?php echo $point['latitude']; ?>, <?php echo $point['longitude']; ?>],
        <?php endforeach; ?>
    ];
    
    // Dibujar la línea de ruta
    var routePath = L.polyline(routeCoordinates, {
        color: '#2F9C95',
        weight: 5,
        opacity: 0.8,
        dashArray: '10, 10'
    }).addTo(map);
    
    // Añadir marcadores especiales para origen y destino
    L.marker([<?php echo $droneData['route'][0]['latitude']; ?>, <?php echo $droneData['route'][0]['longitude']; ?>], {
        title: '<?php echo $droneData['route'][0]['name']; ?>'
    }).bindPopup(`<b><?php echo $droneData['route'][0]['name']; ?></b><br><small>Punto de recogida</small>`).addTo(map);
    
    L.marker([<?php echo end($droneData['route'])['latitude']; ?>, <?php echo end($droneData['route'])['longitude']; ?>], {
        title: '<?php echo end($droneData['route'])['name']; ?>'
    }).bindPopup(`<b><?php echo end($droneData['route'])['name']; ?></b><br><small>Lugar de entrega</small>`).addTo(map);
    
    // Solo animar si el paquete está en tránsito
    <?php if ($package['status'] == 'in_transit'): ?>
    animateDrone();
    <?php endif; ?>
    
    function animateDrone() {
        var currentIndex = 0;
        var nextIndex = 1;
        var steps = 100;
        var step = 0;

        function move() {
            if (step >= steps) {
                currentIndex++;
                nextIndex++;
                step = 0;

                if (nextIndex >= routeCoordinates.length) {
                    // Llegó al destino
                    updateStatus('ready_for_pickup');
                    setTimeout(generatePickupCode, 500); // Llamar a generatePickupCode después de actualizar el estado
                    return;
                }
            }

            // Calcular nueva posición
            var lat = routeCoordinates[currentIndex][0] + 
                     (routeCoordinates[nextIndex][0] - routeCoordinates[currentIndex][0]) * (step / steps);
            var lng = routeCoordinates[currentIndex][1] + 
                     (routeCoordinates[nextIndex][1] - routeCoordinates[currentIndex][1]) * (step / steps);

            // Actualizar posición del dron
            droneMarker.setLatLng([lat, lng]);

            // Actualizar información en tiempo real
            updateTrackingInfo(currentIndex, step, steps);
            updateRouteProgress(currentIndex);

            step++;
            setTimeout(move, 100);
        }

        move();
    }
    
    function updateTrackingInfo(currentIndex, step, totalSteps) {
        var totalDistance = 0.8; // km total
        var completedRatio = (currentIndex + (step / totalSteps)) / (routeCoordinates.length - 1);
        var remainingDistance = (totalDistance * (1 - completedRatio)).toFixed(2);
        var remainingTime = Math.round((remainingDistance / <?php echo $droneData['speed']; ?>) * 60);
        
        // Actualizar UI
        document.querySelector('.info-row:nth-child(3) strong').textContent = remainingDistance + ' km';
        document.querySelector('.info-row:nth-child(4) strong').textContent = remainingTime + ' min';
    }
    
    function updateRouteProgress(currentIndex) {
        document.querySelectorAll('.route-point').forEach((point, index) => {
            point.classList.remove('completed', 'current');
            
            if (index < currentIndex) {
                point.classList.add('completed');
            } else if (index === currentIndex) {
                point.classList.add('current');
            }
        });
    }
    
    function updateStatus(newStatus) {
        document.querySelector('.status-indicator').classList.remove('active', 'completed');
        document.querySelector('.status-indicator').classList.add(newStatus == 'ready_for_pickup' ? 'completed' : 'active');
        document.querySelector('.status-indicator span').textContent = 
            newStatus == 'ready_for_pickup' ? 'Listo para recoger' : 'En ruta';
        
        document.querySelector('.status-badge').textContent = 
            newStatus == 'ready_for_pickup' ? 'Listo para recoger' : 'En ruta';
        document.querySelector('.status-badge').className = 
            'status-badge status-' + newStatus;
    }


    function showPickupNotification(code) {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.id = 'pickup-notification';
        notification.style.position = 'fixed';
        notification.style.top = '0';
        notification.style.left = '0';
        notification.style.width = '100%';
        notification.style.height = '100%';
        notification.style.backgroundColor = 'rgba(0,0,0,0.7)';
        notification.style.display = 'flex';
        notification.style.justifyContent = 'center';
        notification.style.alignItems = 'center';
        notification.style.zIndex = '1000';
        
        // Contenido de la notificación
        notification.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 10px; max-width: 400px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3)">
                <h3 style="color: #2F9C95; margin-top: 0">¡Tu paquete ha llegado!</h3>
                <p style="font-size: 1.2rem">Código de recogida:</p>
                <div style="font-size: 2rem; font-weight: bold; margin: 1rem 0; letter-spacing: 3px">${code}</div>
                <p>Guarda este código para recoger tu paquete.</p>
                <button onclick="document.getElementById('pickup-notification').remove()" 
                        style="background: #2F9C95; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer">
                    Cerrar
                </button>
            </div>
        `;
        
        // Añadir al documento
        document.body.appendChild(notification);
    }
    
    // Asegurar que la ventana emergente se muestre correctamente al finalizar el recorrido
    function generatePickupCode() {
        fetch('generate_code.php?package_id=<?php echo $package['id']; ?>')
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error(`Respuesta no JSON: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mostrar ventana emergente con el código
                    showPickupNotification(data.code);

                    // Actualizar UI
                    document.getElementById('pickup-code-display').textContent = data.code;
                    document.querySelector('.status-indicator').className = 'status-indicator completed';
                    document.querySelector('.status-indicator span').textContent = 'Listo para recoger';
                } else {
                    throw new Error(data.error || 'Error al generar código');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al generar código: ' + error.message);
            });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
