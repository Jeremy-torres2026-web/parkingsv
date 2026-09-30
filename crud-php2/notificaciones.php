<?php
session_start();
$page_title = "Parking SV - ¡Entérate de todo!";
include 'includes/header.php';
include 'conexion.php';
if (isset($_SESSION['mensaje'])) {
    echo $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener el ID del usuario actual
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];
$user_name = $_SESSION['user_name'];

// Obtener las notificaciones del usuario desde la base de datos
$query = "SELECT * FROM notifications 
          WHERE user_id = $user_id 
          ORDER BY created_at DESC";
$result = mysqli_query($conex, $query);

$notificaciones = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notificaciones[] = $row;
}

// Cerrar conexión
mysqli_close($conex);

// Función para obtener el color según el tipo de notificación
function getNotificationColor($type) {
    $colors = [
        'review_response' => '#0C6FF9',    // Azul
        'parking_update' => '#4CAF50',     // Verde
        'price_drop' => '#FF9800',         // Naranja
        'new_feature' => '#9C27B0',        // Morado
        'security_alert' => '#F44336',     // Rojo
        'saved_parking_news' => '#03A9F4', // Azul claro
        'system_news' => '#607D8B',        // Gris
        'reservation_reminder' => '#FFC107', // Amarillo
        'promotion' => '#E91E63',          // Rosa
        'owner_specific' => '#2E7D32',     // Verde oscuro
        'admin_alert' => '#B71C1C'         // Rojo oscuro
    ];
    
    return $colors[$type] ?? '#607D8B'; // Color por defecto
}

// Función para obtener el nombre del tipo de notificación
function getNotificationTypeName($type) {
    $names = [
        'review_response' => 'Respuesta a reseña',
        'parking_update' => 'Actualización de parqueo',
        'price_drop' => 'Oferta de precio',
        'new_feature' => 'Nueva función',
        'security_alert' => 'Alerta de seguridad',
        'saved_parking_news' => 'Noticias de parqueo guardado',
        'system_news' => 'Noticias del sistema',
        'reservation_reminder' => 'Recordatorio de reservación',
        'promotion' => 'Promoción',
        'owner_specific' => 'Información para propietarios',
        'admin_alert' => 'Alerta de administrador'
    ];
    
    return $names[$type] ?? 'Notificación';
}

// Función para formatear la fecha de forma amigable
function formatFriendlyDate($date_str) {
    $date = new DateTime($date_str);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0) {
        return 'Hoy a las ' . $date->format('H:i');
    } elseif ($diff->days == 1) {
        return 'Ayer a las ' . $date->format('H:i');
    } elseif ($diff->days < 7) {
        return 'Hace ' . $diff->days . ' días';
    } else {
        return $date->format('d/m/Y H:i');
    }
}
?>
  <!-- CSS específico -->
  <?php if(basename($_SERVER['PHP_SELF']) == 'notificaciones.php'): ?>
    <link rel="stylesheet" href="/crud-php2/assets/css/pages/notificaciones.css">
  <?php endif; ?>

    <div class="notifications-container">
        <div class="header">
            <h1><i class="fas fa-bell"></i> Tus notificaciones</h1>
            <div class="header-actions">
                <button class="btn btn-outline">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <button class="btn btn-primary" id="markAllRead">
                    <i class="fas fa-check-double"></i> Marcar todas como leídas
                </button>
            </div>
        </div>
        
        <div class="notifications-list">
            <?php if (count($notificaciones) > 0): ?>
                <?php foreach ($notificaciones as $notif): 
                    $color = getNotificationColor($notif['notification_type']);
                    $type_name = getNotificationTypeName($notif['notification_type']);
                    $date_formatted = formatFriendlyDate($notif['created_at']);
                    $is_read = $notif['is_read'];
                ?>
                    <div class="notification-item <?php echo $is_read ? 'notification-read' : 'notification-unread'; ?>" 
                         style="border-left-color: <?php echo $color; ?>"
                         data-id="<?php echo $notif['id']; ?>">
                        <div class="notification-header">
                            <span class="notification-type" style="background-color: <?php echo $color; ?>">
                                <?php echo $type_name; ?>
                            </span>
                            <span class="notification-date">
                                <?php echo $date_formatted; ?>
                            </span>
                        </div>
                        
                        <div class="notification-title">
                            <?php echo htmlspecialchars($notif['title']); ?>
                        </div>
                        
                        <div class="notification-content">
                            <?php echo htmlspecialchars($notif['content']); ?>
                        </div>
                        
                        <div class="notification-actions">
                            <button class="action-btn" onclick="toggleActionMenu(this)">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="action-menu">
                                <div class="action-menu-item" onclick="toggleReadStatus(<?php echo $notif['id']; ?>)">
                                    <i class="fas fa-<?php echo $is_read ? 'envelope' : 'envelope-open'; ?>"></i>
                                    <?php echo $is_read ? 'Marcar como no leída' : 'Marcar como leída'; ?>
                                </div>
                                <div class="action-menu-item" onclick="deleteNotification(<?php echo $notif['id']; ?>)">
                                    <i class="fas fa-trash"></i> Eliminar
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No tienes notificaciones</h3>
                    <p>Cuando tengas nuevas notificaciones, aparecerán aquí.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
<script src="/crud-php2/assets/js/pages/notificaciones.js"></script> <!-- Script específico -->