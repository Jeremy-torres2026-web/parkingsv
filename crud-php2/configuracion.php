<?php
// configuracion.php
session_start();
include("conexion.php");
$page_title = "Parking SV - Personaliza tu Experiencia";
include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/pages/configuracion.css">

</head>
<body>
    <div class="container">
        <header>
            <div class="logo">Parking<span>SV</span></div>
            <div class="user-info">
                <div>Bienvenido, <strong>Usuario</strong></div>
                <div class="user-avatar">U</div>
            </div>
        </header>
        
        <h1 class="page-title">Personaliza a tu gusto!</h1>
        
        <div class="settings-container">
            <!-- Modo día/noche -->
            <div class="settings-section">
                <h2 class="section-title"><i class="fas fa-sun"></i> Modo día/noche</h2>
                <div class="switch-container">
                    <span>Modo claro</span>
                    <label class="switch">
                        <input type="checkbox" id="theme-toggle">
                        <span class="slider"></span>
                    </label>
                    <span>Modo oscuro</span>
                </div>
            </div>
            
            <!-- Tamaño de letra -->
            <div class="settings-section">
                <h2 class="section-title"><i class="fas fa-text-height"></i> Tamaño de letra</h2>
                <div class="font-size-container">
                    <div class="font-btn" data-size="small">Pequeño</div>
                    <div class="font-btn active" data-size="medium">Mediano</div>
                    <div class="font-btn" data-size="large">Grande</div>
                </div>
            </div>
            
            <!-- Preferencias -->
            <div class="settings-section">
                <h2 class="section-title"><i class="fas fa-cog"></i> Preferencias</h2>
                <div class="preference-item">
                    <div class="preference-label">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Ubicación</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="location-toggle" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="preference-item">
                    <div class="preference-label">
                        <i class="fas fa-thumbs-up"></i>
                        <span>Recomendaciones</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="recommendations-toggle" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="preference-item">
                    <div class="preference-label">
                        <i class="fas fa-bell"></i>
                        <span>Notificaciones</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="notifications-toggle" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Floating Button for Language -->
    <button class="floating-btn" id="language-btn">
        <i class="fas fa-globe"></i>
    </button>
    
    <!-- Language Modal -->
    <div class="modal" id="language-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cambiar idioma</h3>
                <div class="close-btn">&times;</div>
            </div>
            <div class="modal-body">
                <div class="language-option active">
                    <div class="language-flag">🇪🇸</div>
                    <div class="language-name">Español</div>
                    <div class="language-check"><i class="fas fa-check"></i></div>
                </div>
                <div class="language-option">
                    <div class="language-flag">🇺🇸</div>
                    <div class="language-name">English</div>
                    <div class="language-check"><i class="fas fa-check"></i></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="save-language">Guardar cambios</button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/pages/configuracion.js"></script>
<?php include 'includes/footer.php'; ?>