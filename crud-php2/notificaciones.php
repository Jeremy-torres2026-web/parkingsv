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


// ==========================================
// FUNCIÓN: COLOR SEGÚN TIPO DE NOTIFICACIÓN
// ==========================================

function getNotificationColor($type) {

    $colors = [
        'review_response' => '#0C6FF9',
        'parking_update' => '#4CAF50',
        'price_drop' => '#FF9800',
        'new_feature' => '#9C27B0',
        'security_alert' => '#F44336',
        'saved_parking_news' => '#03A9F4',
        'system_news' => '#607D8B',
        'reservation_reminder' => '#FFC107',
        'promotion' => '#E91E63',
        'owner_specific' => '#2E7D32',
        'admin_alert' => '#B71C1C'
    ];

    return $colors[$type] ?? '#607D8B';
}


// ==========================================
// FUNCIÓN: NOMBRE DEL TIPO DE NOTIFICACIÓN
// ==========================================

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


// ==========================================
// FUNCIÓN: FECHA AMIGABLE
// ==========================================

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

<!-- ==========================================
     CSS ESPECÍFICO
     ========================================== -->

<?php if (basename($_SERVER['PHP_SELF']) == 'notificaciones.php'): ?>

<link rel="stylesheet" href="/crud-php2/assets/css/pages/notificaciones.css">

<?php endif; ?>


<!-- ==========================================
     CONTENEDOR DE NOTIFICACIONES
     ========================================== -->

<div class="notifications-container">

    <div class="header">

        <h1>
            <i class="fas fa-bell"></i>
            Tus notificaciones
        </h1>

        <div class="header-actions">

            <button class="btn btn-outline">
                <i class="fas fa-filter"></i>
                Filtrar
            </button>

            <button class="btn btn-primary" id="markAllRead">

                <i class="fas fa-check-double"></i>
                Marcar todas como leídas

            </button>

        </div>

    </div>


    <!-- ==========================================
         LISTA DE NOTIFICACIONES
         ========================================== -->

    <div class="notifications-list">

        <?php if (count($notificaciones) > 0): ?>

            <?php foreach ($notificaciones as $notif):

                $color = getNotificationColor(
                    $notif['notification_type']
                );

                $type_name = getNotificationTypeName(
                    $notif['notification_type']
                );

                $date_formatted = formatFriendlyDate(
                    $notif['created_at']
                );

                $is_read = $notif['is_read'];

            ?>

                <div
                    class="notification-item <?php echo $is_read ? 'notification-read' : 'notification-unread'; ?>"
                    style="border-left-color: <?php echo $color; ?>"
                    data-id="<?php echo $notif['id']; ?>"
                >

                    <div class="notification-header">

                        <span
                            class="notification-type"
                            style="background-color: <?php echo $color; ?>"
                        >
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

                        <button
                            class="action-btn"
                            onclick="toggleActionMenu(this)"
                        >

                            <i class="fas fa-ellipsis-v"></i>

                        </button>


                        <div class="action-menu">

                            <div
                                class="action-menu-item"
                                onclick="toggleReadStatus(<?php echo $notif['id']; ?>)"
                            >

                                <i class="fas fa-<?php echo $is_read ? 'envelope' : 'envelope-open'; ?>"></i>

                                <?php echo $is_read
                                    ? 'Marcar como no leída'
                                    : 'Marcar como leída';
                                ?>

                            </div>


                            <div
                                class="action-menu-item"
                                onclick="deleteNotification(<?php echo $notif['id']; ?>)"
                            >

                                <i class="fas fa-trash"></i>

                                Eliminar

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>


        <?php else: ?>

            <div class="no-notifications">

                <i class="fas fa-bell-slash"></i>

                <h3>
                    No tienes notificaciones
                </h3>

                <p>
                    Cuando tengas nuevas notificaciones,
                    aparecerán aquí.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>


<!-- ==========================================
     🦫 CAPIBARA DE TRÁNSITO
     ========================================== -->

<div class="capibara-transito">

    <img
        src="/crud-php2/assets/img/Capybb.png"
        alt="Capibara de tránsito"
    >

</div>


<!-- ==========================================
     FOOTER
     ========================================== -->

<?php include 'includes/footer.php'; ?>


<!-- ==========================================
     JAVASCRIPT
     ========================================== -->

<script src="/crud-php2/assets/js/pages/notificaciones.js"></script>


<!-- ==========================================
     ANIMACIÓN DEL CAPIBARA
     ========================================== -->

<style>

.capibara-transito {

    position: fixed;

    bottom: 12px;

    left: 25px;

    width: 85px;

    z-index: 1000;

    pointer-events: none;

    animation: capibaraFlota 2.5s ease-in-out infinite;

}


.capibara-transito img {

    display: block;

    width: 100%;

    height: auto;

    object-fit: contain;

    animation: capibaraBalanceo 2.5s ease-in-out infinite;

}


/* Movimiento hacia arriba y abajo */

@keyframes capibaraFlota {

    0%,
    100% {

        transform: translateY(0);

    }

    50% {

        transform: translateY(-7px);

    }

}


/* Pequeño balanceo */

@keyframes capibaraBalanceo {

    0%,
    100% {

        transform: rotate(-2deg);

    }

    50% {

        transform: rotate(2deg);

    }

}


/* Adaptación para celulares */

@media (max-width: 600px) {

    .capibara-transito {

        width: 65px;

        bottom: 8px;

        left: 10px;

    }

}

</style>