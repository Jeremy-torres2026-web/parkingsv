<?php
session_start();
include("conexion.php");

// Desactiva los reportes automáticos de MySQLi para manejar errores manualmente
mysqli_report(MYSQLI_REPORT_OFF);

// LOGOUT
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    $_SESSION['mensaje'] = '<div class="message success">Has cerrado sesión.</div>';
    header("Location: index.php");
    exit();
}

// Registro en un solo paso
if (isset($_SESSION['user_name']) && isset($_POST['register'])) {
    $_SESSION['mensaje'] = '<div class="message error">Ya tienes una sesión iniciada. Por favor, cierra sesión antes de crear otra cuenta.</div>';
    header("Location: index.php");
    exit();
} elseif (isset($_POST['register'])) {
    $full_name = trim($_POST['name']) . ' ' . trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password_hash = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $phone_number = trim($_POST['phone']);
    $user_type = $_POST['userType'];
    $date_of_birth = isset($_POST['birth_date']) && $_POST['birth_date'] ? $_POST['birth_date'] : null;
    $business_name = isset($_POST['business_name']) && $_POST['business_name'] ? $_POST['business_name'] : null;
    $created_at = date('Y-m-d H:i:s');
    $location_permission = 0; // Por defecto

    // Solo uno de los dos campos se llena según el tipo de usuario
    if ($user_type == "customer") {
        $business_name = null;
    } elseif ($user_type == "owner") {
        $date_of_birth = null;
    }

    $consulta = "INSERT INTO users 
        (full_name, email, password_hash, phone_number, date_of_birth, business_name, user_type, created_at, location_permission)
        VALUES (
            '" . mysqli_real_escape_string($conex, $full_name) . "',
            '" . mysqli_real_escape_string($conex, $email) . "',
            '" . mysqli_real_escape_string($conex, $password_hash) . "',
            '" . mysqli_real_escape_string($conex, $phone_number) . "',
            " . ($date_of_birth ? "'" . mysqli_real_escape_string($conex, $date_of_birth) . "'" : "NULL") . ",
            " . ($business_name ? "'" . mysqli_real_escape_string($conex, $business_name) . "'" : "NULL") . ",
            '" . mysqli_real_escape_string($conex, $user_type) . "',
            '" . mysqli_real_escape_string($conex, $created_at) . "',
            " . intval($location_permission) . "
        )";
    $resultado = mysqli_query($conex, $consulta);

    if ($resultado) {
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_type'] = $user_type; // Guardamos el tipo de usuario
        $user_id = mysqli_insert_id($conex);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['mensaje'] = '<div class="message success">¡Bienvenido, ' . htmlspecialchars($full_name) . '!</div>';
    } else {
        if (mysqli_errno($conex) == 1062) {
            $_SESSION['mensaje'] = '<div class="message error">El correo ya está registrado. Intenta con otro.</div>';
        } else {
            $_SESSION['mensaje'] = '<div class="message error">Error al crear usuario: ' . mysqli_error($conex) . '</div>';
        }
    }
    header("Location: index.php");
    exit();
}

// Login
if (isset($_POST['login'])) {
    $email = trim($_POST['loginEmail']);
    $password = trim($_POST['loginPassword']);

    // Modificar la consulta para incluir profile_picture
    $consulta = "SELECT id, full_name, email, password_hash, user_type, profile_picture FROM users WHERE email='" . mysqli_real_escape_string($conex, $email) . "'";
    $resultado = mysqli_query($conex, $consulta);

    if ($row = mysqli_fetch_assoc($resultado)) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_name'] = $row['full_name'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_type'] = $row['user_type'];
            // Añadir esta línea para guardar la imagen de perfil en la sesión
            $_SESSION['user_profile_picture'] = $row['profile_picture'] ?: '/crud-php2/assets/images/pfp default.jpeg';
            
            $_SESSION['mensaje'] = '<div class="message success">¡Bienvenido de nuevo, ' . htmlspecialchars($row['full_name']) . '!</div>';
        } else {
            $_SESSION['mensaje'] = '<div class="message error">Correo o contraseña incorrectos.</div>';
        }
    } else {
        $_SESSION['mensaje'] = '<div class="message error">Correo o contraseña incorrectos.</div>';
    }
    header("Location: index.php");
    exit();
}

$page_title = "Parking SV - Inicio";
include 'includes/header.php';
?>

  <!-- CSS específico para index.php -->
  <?php if(basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
    <link rel="stylesheet" href="/crud-php2/assets/css/pages/index.css">
  <?php endif; ?>

<!-- ===== SECCIÓN PROBLEMÁTICA ===== -->
<section class="problem-wrapper snap-section" id="problematica">
    <h1 class="problem-title">
        <span class="highlight">Todos</span> nos enfrentamos a esta problemática.<br>
        En El Salvador y en el mundo.
    </h1>
    <div class="problem-content">
        <div class="problem-left">
            <p class="problem-desc">
                Cada día, miles de nosotros perdemos tiempo, dinero y energía buscando parqueo.
                El tráfico y la falta de información dificultan estacionarse con eficiencia.
            </p>
            <p class="problem-desc">
                Parking SV nace como una solución rápida y confiable para encontrar espacios disponibles, sin estrés ni pérdidas de tiempo.
            </p>
        </div>
        <div class="problem-right">
            <div class="floating-images">
                <img src="img sources/bubble1.png" alt="Solución Parking SV">
            </div>
        </div>
    </div>
    <div class="buttons">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <button id="openLoginModal">¡Empezar ya!</button>
            <button id="openSignupModal">¡Publicar mi espacio ya!</button>
        <?php else: ?>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                <a href="parqueos-publicados.php" class="btn-action">¡Empezar ya!</a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner'): ?>
                <a href="publicar-parqueo.php" class="btn-action">¡Publicar mi espacio ya!</a>
            <?php else: ?>
                <a href="parqueos-publicados.php" class="btn-action"><strong>¡Empezar ya!</strong></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- ===== TARJETA 1 ===== -->
<section class="carousel-slide snap-section" id="solucion1">
    <h2>¡Parking SV tiene la solución!</h2>
    <div class="carousel-slide-content">
        <div class="carousel-slide-text">
            <p>Parking SV es la solución inteligente, rápida y local para conectar personas que necesitan parqueo con quienes tienen un espacio disponible.</p>
            <p class="feature-item">✔️ Sin complicaciones, sin perder tiempo, sin estrés.</p>
            <p class="feature-item">✔️ Publica tu lote o empieza a ganar</p>
            <p class="feature-item">✔️ Encontrá un parqueo cerca, confiable y en minutos.</p>
        </div>
        <div class="carousel-slide-img">
            <img src="img sources/solution.png" alt="solucion img">
        </div>
    </div>
</section>

<!-- ===== TARJETA 2 ===== -->
<section class="carousel-slide snap-section" id="solucion2">
    <h2>¿Cómo funciona?</h2>
    <div class="carousel-slide-content">
        <div class="carousel-slide-text">
            <p class="feature-item">📍 Explorá en la lista de parqueos en tiempo real</p>
            <p class="feature-item">📌 Encontrá parqueos cerca de tu destino</p>
            <p class="feature-item">✅ Verificá la información del espacio y la calificación del parqueo</p>
            <p class="feature-item">🚗 Selecciona un parqueo, mira el mapa, maneja en Waze y listo</p>
        </div>
        <div class="carousel-slide-img">
            <img src="img sources/sample.png" alt="video">
        </div>
    </div>
</section>

<!-- ===== TARJETA 3 ===== -->
<section class="carousel-slide snap-section" id="solucion3">
    <h2>🚀 ¿Por qué Parking SV?</h2>
    <div class="carousel-slide-content">
        <div class="carousel-slide-text">
            <p class="feature-item">💙 Hecha por salvadoreños, para salvadoreños</p>
            <p class="feature-item">💲 Sin comisiones, sin apps complicadas</p>
            <p class="feature-item">🔒 Comunidad verificada</p>
            <p class="feature-item">⏱️ Ahorro de tiempo, combustible y dinero</p>
        </div>
        <div class="carousel-slide-img">
            <img src="img sources/nosotros.png" alt="por qué Parking SV">
        </div>
    </div>
</section>

<!-- Modal para Mensajes (oculto por defecto) -->
<div class="message-modal" id="messageModal" style="display: none;">
  <div class="message-modal-content">
    <span class="close-message-modal">&times;</span>
    <div id="messageContent">
      <?php if (isset($_SESSION['mensaje'])): ?>
        <?php echo $_SESSION['mensaje']; ?>
        <?php unset($_SESSION['mensaje']); ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal Login/Signup (estructura visual bonita y funcional) -->
<div class="login-signup-modal" id="loginSignupModal">
  <div class="modal-content">
    <span class="close-modal" id="closeModalBtn">&times;</span>
    <div class="auth-tabs">
      <button id="switchLogin" class="auth-tab active">Iniciar sesión</button>
      <button id="switchSignup" class="auth-tab">Registrarse</button>
    </div>
    <div class="logo-container">
      <img src="img sources/Logo_Parking_SV-bg.png" alt="Logo Parking SV" class="auth-logo">
    </div>
    <form id="loginForm" method="post" action="">
      <div class="form-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="loginEmail" placeholder="Correo electrónico" required />
      </div>
      <div class="form-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="loginPassword" placeholder="Contraseña" required />
      </div>
      <button type="submit" name="login" class="btn-auth btn-login">Iniciar sesión</button>
    </form>
    <form id="signupForm" method="post" action="">
      <div class="form-group">
        <i class="fas fa-user"></i>
        <input type="text" name="name" placeholder="Nombre" required />
      </div>
      <div class="form-group">
        <i class="fas fa-user"></i>
        <input type="text" name="lastName" placeholder="Apellidos" required />
      </div>
      <div class="form-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" placeholder="Correo electrónico" required />
      </div>
      <div class="form-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Contraseña" required />
      </div>
      <div class="form-group">
        <label for="userTypeSelect">¿Eres cliente o propietario?</label>
        <select name="userType" id="userTypeSelect" required>
          <option value="">Selecciona una opción</option>
          <option value="customer">Cliente</option>
          <option value="owner">Propietario</option>
        </select>
      </div>
      <div class="form-group">
        <i class="fas fa-phone"></i>
        <input type="text" name="phone" placeholder="Teléfono" required autocomplete="tel" />
      </div>
      <div class="form-group" id="birthDateGroup" style="display:none;">
        <i class="fas fa-calendar-alt"></i>
        <p>Fecha de nacimiento</p>
        <input type="date" name="birth_date" placeholder="Fecha de nacimiento" id="birthDateInput" />
      </div>
      <div class="form-group" id="businessNameGroup" style="display:none;">
        <i class="fas fa-store"></i>
        <input type="text" name="business_name" placeholder="Nombre del negocio" id="businessNameInput" />
      </div>
      <button type="submit" name="register" class="btn-auth btn-signup">Registrarse</button>
    </form>
  </div>
</div>

<div class="ad-card snap-section" id="anuncios">
  <h3 class="ad-title">Anúnciate Aquí</h3>
  <p class="ad-subtitle">Ejemplos de anunciantes potenciales:</p>
  <ul class="ad-examples">
    <li>Lugares turísticos</li>
    <li>Carwash para autos</li>
    <li>Talleres mecánicos</li>
    <li>Tiendas de accesorios vehiculares</li>
    <li>Restaurantes cercanos</li>
    <li>Servicios de taxi</li>
  </ul>
</div>
<!-- Fin Modal Login/Signup -->
<?php include 'includes/footer.php'; ?>
<script src="/crud-php2/assets/js/pages/index.js"></script>