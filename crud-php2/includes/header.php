<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page_title ?? 'Parking SV' ?></title>

    <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,400;0,700;1,400&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">  
  <!-- Hojas de estilo globales -->
  <link rel="stylesheet" href="/crud-php2/assets/css/global.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Favicon -->
  <link rel="icon" href="/crud-php2/img sources/Logo_Parking_SV-bg.png">
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?> <?php echo $is_logged_in ? 'user-logged-in' : 'user-not-logged-in'; ?>">
  <!-- ========== HEADER/NAVBAR PRINCIPAL ========== -->
  <header class="navbar">
    
    <!-- Logo y nombre de la marca -->
    <div class="logo">
      <a href="index.php">
        <img src="/crud-php2/img sources/Logo_Parking_SV-bg.png" alt="Logo Parking SV">
      </a>
      <h2 class="name">Parking SV</h2>
    </div>
    
    <!-- Botón para menú hamburguesa (dispositivos móviles) -->
    <button class="navbar-toggle" id="navbarToggle" aria-label="Abrir menú">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <?php
    // Definición de las páginas principales de navegación
    $current_page = basename($_SERVER['REQUEST_URI']);
    $pages = [
        'index.php' => [
            'class' => 'inicio-link',
            'icon' => '/crud-php2/img sources/casa parking 2.png',
            'text' => 'Inicio'
        ],
        'parqueos-publicados.php' => [
            'class' => 'parqueos-link',
            'icon' => '/crud-php2/img sources/icon_parqueos_publicados-bg.png',
            'text' => 'Parqueos'
        ],
        'about.php' => [
            'class' => 'about-link',
            'icon' => '/crud-php2/img sources/icon_about-bg.png',
            'text' => 'Sobre nosotros'
        ]
    ];
    ?>

    <!-- Menú de navegación principal -->
    <nav>
      <ul class="navbar-links">
        
        <!-- Iteración para generar los enlaces de las páginas definidas -->
        <?php foreach ($pages as $page => $data): ?>
        <li class="nav-item">
          <a href="/crud-php2/<?= $page ?>" 
             class="nav-link <?= $data['class'] ?> 
                    <?= ($current_page == $page || strpos($current_page, $page) !== false) ? 'active' : '' ?>">
            <img class="<?= str_replace('-link', '-icon', $data['class']) ?>" 
                 src="<?= $data['icon'] ?>" 
                 alt="Icono <?= strtolower($data['text']) ?>" 
                 width="30" height="30">
            <?= $data['text'] ?>
          </a>
        </li>
        <?php endforeach; ?>
        
        <!-- Menú desplegable de usuario -->
        <li class="user-dropdown">
          <a href="#" id="userMenuBtn">
            <?php
            // Obtener la foto de perfil de la sesión o usar la por defecto
            $profile_picture = isset($_SESSION['user_profile_picture']) ? $_SESSION['user_profile_picture'] : '/crud-php2/assets/images/pfp default.jpeg';
            ?>
            <img src="<?php echo $profile_picture; ?>" 
                 alt="Foto de perfil" 
                 class="user-profile-picture"
                 id="userMenuIcon">
          </a>
          
          <!-- Submenú de opciones de usuario -->
          <ul class="user-dropdown-menu" id="userDropdown">
            <li><a href="/crud-php2/mi-cuenta.php" class="restricted-link"><i class="fas fa-user"></i> Mi cuenta</a></li>
            <li><a href="/crud-php2/guardados.php" class="restricted-link"><i class="fas fa-bookmark"></i> Guardados</a></li>
            <li><a href="/crud-php2/notificaciones.php" class="restricted-link"><i class="fas fa-bell"></i> Notificaciones</a></li>
            <li><a href="/crud-php2/configuracion.php" class="restricted-link"><i class="fas fa-cog"></i> Configuración</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>
  
  <!-- ========== MODAL DE SESIÓN REQUERIDA ========== -->
  <div id="sessionModal" class="session-modal">
    <div class="session-modal-content">
      <div class="session-modal-header">
        <div class="session-modal-icon">
          <img src="/crud-php2/img sources/Locked.png" alt="Bloqueado" class="lock-icon">
        </div>
        <h2 class="session-modal-title">¡No iniciaste sesión!</h2>
        <p class="session-modal-subtitle">
          Inicia sesión para desbloquear estos beneficios exclusivos
        </p>
      </div>
      
      <div class="session-modal-features-container">
        <div class="features-scroll">
          <div class="session-feature-item">
            <i class="fas fa-user-cog session-feature-icon"></i>
            <div class="session-feature-text">
              <h3>Personalización total</h3>
              <p>Adaptamos tu experiencia a tus preferencias</p>
            </div>
          </div>
          <div class="session-feature-item">
            <i class="fas fa-bookmark session-feature-icon"></i>
            <div class="session-feature-text">
              <h3>Organiza favoritos</h3>
              <p>Guarda tus parqueos preferidos</p>
            </div>
          </div>
          <div class="session-feature-item">
            <i class="fas fa-bell session-feature-icon"></i>
            <div class="session-feature-text">
              <h3>Notificaciones</h3>
              <p>Alertas de disponibilidad y promociones</p>
            </div>
          </div>
          <div class="session-feature-item">
            <i class="fas fa-calendar-check session-feature-icon"></i>
            <div class="session-feature-text">
              <h3>Reservas anticipadas</h3>
              <p>Asegura tu espacio antes de llegar</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="session-modal-actions">
        <button id="sessionModalLogin" class="btn session-btn-primary">
          <i class="fas fa-sign-in-alt"></i>
          Iniciar Sesión
        </button>
        <button id="sessionModalGuest" class="btn session-btn-guest">
          <i class="fas fa-user-secret"></i>
          Explorar como invitado
        </button>
        <p class="account-prompt">
          ¿No tienes cuenta? <span class="register-link">Crear una cuenta</span>
        </p>
      </div>
    </div>
  </div>
  
  <!-- ========== CONTENIDO PRINCIPAL ========== -->
  <main>