<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'conexion.php';
require_once 'libs/PHPMailer/mailer.php';

$errors = [];
$success_message = '';
$form_data = [];

// Función para validar y limpiar entradas
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Función para validar email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Función para validar contraseña (mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número)
function validate_password($password) {
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password) 
        && preg_match('/[a-z]/', $password) 
        && preg_match('/[0-9]/', $password);
}

// Rate limiting: verificar intentos de registro por IP
function check_rate_limit($conex, $ip) {
    $ip = mysqli_real_escape_string($conex, $ip);
    
    // Crear tabla si no existe
    $create_table = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        attempt_type VARCHAR(20) NOT NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_type_time (ip_address, attempt_type, attempted_at)
    )";
    mysqli_query($conex, $create_table);
    
    $query = "SELECT COUNT(*) as attempts 
              FROM login_attempts 
              WHERE ip_address = '$ip' 
              AND attempt_type = 'register' 
              AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    
    $result = mysqli_query($conex, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['attempts'] < 5;
}

// Registrar intento
function log_attempt($conex, $ip, $type = 'register') {
    $ip = mysqli_real_escape_string($conex, $ip);
    $type = mysqli_real_escape_string($conex, $type);
    
    $query = "INSERT INTO login_attempts (ip_address, attempt_type, attempted_at) 
              VALUES ('$ip', '$type', NOW())";
    mysqli_query($conex, $query);
}

// Limpiar intentos antiguos (más de 15 minutos)
function clean_old_attempts($conex) {
    $query = "DELETE FROM login_attempts 
              WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    mysqli_query($conex, $query);
}

// Función para verificar y limpiar integridad de la base de datos
function check_database_integrity($conex) {
    $errors = [];
    
    // Verificar favorites huérfanos
    $query = "SELECT COUNT(*) as orphaned FROM favorites f 
              LEFT JOIN users u ON f.user_id = u.id 
              WHERE u.id IS NULL";
    $result = mysqli_query($conex, $query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['orphaned'] > 0) {
        $errors[] = "Existen {$row['orphaned']} favoritos huérfanos";
        
        // Limpiar automáticamente
        $cleanup_query = "DELETE f FROM favorites f 
                         LEFT JOIN users u ON f.user_id = u.id 
                         WHERE u.id IS NULL";
        mysqli_query($conex, $cleanup_query);
    }
    
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar token CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Token de seguridad inválido. Por favor, recarga la página.');
        }

        // Obtener IP del cliente
        $client_ip = $_SERVER['REMOTE_ADDR'];

        // Limpiar intentos antiguos
        clean_old_attempts($conex);

        // Verificar y limpiar integridad de la base de datos
        $integrity_errors = check_database_integrity($conex);
        if (!empty($integrity_errors)) {
            error_log("Problemas de integridad en la BD: " . implode(", ", $integrity_errors));
        }

        // Verificar rate limit
        if (!check_rate_limit($conex, $client_ip)) {
            throw new Exception('Demasiados intentos de registro. Por favor, espera 15 minutos.');
        }

        // Obtener y sanitizar datos del formulario
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user_type = sanitize_input($_POST['user_type'] ?? '');
        $terms_accepted = isset($_POST['terms_accepted']);

        // Guardar datos del formulario para repoblar en caso de error
        $form_data = [
            'full_name' => $full_name,
            'email' => $email,
            'user_type' => $user_type,
            'terms_accepted' => $terms_accepted
        ];

        // Validaciones
        if (empty($full_name)) {
            $errors['full_name'] = 'El nombre completo es requerido.';
        } elseif (strlen($full_name) < 3) {
            $errors['full_name'] = 'El nombre debe tener al menos 3 caracteres.';
        }

        if (empty($email)) {
            $errors['email'] = 'El correo electrónico es requerido.';
        } elseif (!validate_email($email)) {
            $errors['email'] = 'Por favor, ingresa un correo válido.';
        }

        if (empty($user_type) || !in_array($user_type, ['customer', 'owner'])) {
            $errors['user_type'] = 'Debes seleccionar un tipo de usuario.';
        }

        if (empty($password)) {
            $errors['password'] = 'La contraseña es requerida.';
        } elseif (!validate_password($password)) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.';
        }

        if (empty($confirm_password)) {
            $errors['confirm_password'] = 'Debes confirmar tu contraseña.';
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Las contraseñas no coinciden.';
        }

        if (!$terms_accepted) {
            $errors['terms'] = 'Debes aceptar los términos y condiciones.';
        }

        // Si no hay errores, proceder con el registro
        if (empty($errors)) {
            // Hash de la contraseña con Argon2id (o bcrypt como fallback)
            if (defined('PASSWORD_ARGON2ID')) {
                $password_hash = password_hash($password, PASSWORD_ARGON2ID);
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
            }

            // Iniciar transacción
            mysqli_begin_transaction($conex);

            try {
                // Escapar datos para prevenir SQL injection
                $full_name_escaped = mysqli_real_escape_string($conex, $full_name);
                $email_escaped = mysqli_real_escape_string($conex, $email);
                $password_hash_escaped = mysqli_real_escape_string($conex, $password_hash);
                $user_type_escaped = mysqli_real_escape_string($conex, $user_type);

                // VERIFICAR SI EL EMAIL YA EXISTE (aquí es donde debe estar esta lógica)
                $check_email_query = "SELECT id, email_verified FROM users WHERE email = '$email_escaped'";
                $email_result = mysqli_query($conex, $check_email_query);
                
                $user_id = null;
                $is_new_user = true;

                if (mysqli_num_rows($email_result) > 0) {
                    $existing_user = mysqli_fetch_assoc($email_result);
                    
                    if ($existing_user['email_verified'] == 1) {
                        // Email ya verificado - mostrar error
                        throw new Exception('Este correo electrónico ya está registrado y verificado. Por favor, inicia sesión o utiliza otro correo.');
                    } else {
                        // Email existe pero no está verificado - actualizar registro
                        $is_new_user = false;
                        $user_id = $existing_user['id'];
                        
                        $update_query = "UPDATE users SET 
                                        full_name = '$full_name_escaped', 
                                        password_hash = '$password_hash_escaped', 
                                        user_type = '$user_type_escaped',
                                        created_at = NOW(),
                                        email_verified = 0 
                                        WHERE id = $user_id";
                        
                        if (!mysqli_query($conex, $update_query)) {
                            throw new Exception('Error al actualizar el registro existente: ' . mysqli_error($conex));
                        }
                        
                        // Eliminar códigos de verificación anteriores
                        $delete_verification = "DELETE FROM verifications WHERE email = '$email_escaped'";
                        mysqli_query($conex, $delete_verification);
                    }
                } else {
                    // Email no existe - crear nuevo usuario
                    $insert_query = "INSERT INTO users (full_name, email, password_hash, user_type, created_at, email_verified) 
                                    VALUES ('$full_name_escaped', '$email_escaped', '$password_hash_escaped', '$user_type_escaped', NOW(), 0)";
                    
                    if (!mysqli_query($conex, $insert_query)) {
                        throw new Exception('Error al registrar el usuario: ' . mysqli_error($conex));
                    }
                    
                    $user_id = mysqli_insert_id($conex);
                }

                // Generar código de verificación de 6 dígitos
                $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                
                // Guardar en tabla verifications
                $verification_query = "INSERT INTO verifications (email, code, created_at, attempts) 
                                    VALUES ('$email_escaped', '$verification_code', NOW(), 0)";
                
                if (!mysqli_query($conex, $verification_query)) {
                    throw new Exception('Error al crear código de verificación: ' . mysqli_error($conex));
                }

                // Enviar email de verificación
                $email_subject = 'Verifica tu cuenta - Parking SV';
                $email_body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #0C6FF9;'>¡Bienvenido a Parking SV!</h2>
                        <p>Hola <strong>{$full_name}</strong>,</p>
                        <p>Gracias por registrarte. Tu código de verificación es:</p>
                        <div style='background: #f5f7fa; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                            <h1 style='color: #0C6FF9; font-size: 32px; letter-spacing: 5px; margin: 0;'>{$verification_code}</h1>
                        </div>
                        <p>Este código expirará en <strong>10 minutos</strong>.</p>
                        <p>Si no solicitaste este registro, ignora este mensaje.</p>
                        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                        <p style='color: #666; font-size: 12px;'>Parking SV - Sistema de Gestión de Estacionamientos</p>
                    </div>
                ";

                if (!send_email($email, $full_name, $email_subject, $email_body)) {
                    throw new Exception('Error al enviar el correo de verificación. Por favor, intenta nuevamente.');
                }

                // Confirmar transacción
                mysqli_commit($conex);

                // Registrar intento exitoso
                log_attempt($conex, $client_ip, 'register_success');

                // Guardar email en sesión para verificación
                $_SESSION['pending_verification_email'] = $email;
                $_SESSION['verification_user_id'] = $user_id;

                // Redirigir a página de verificación
                header('Location: verify-email.php');
                exit();

            } catch (Exception $e) {
                // Revertir transacción en caso de error
                mysqli_rollback($conex);
                throw $e;
            }
        }

        // Registrar intento fallido
        log_attempt($conex, $client_ip, 'register_failed');

    } catch (Exception $e) {
        $errors['general'] = $e->getMessage();
    }
}
?>
<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="assets/css/pages/register.css">

<div class="register-container">
    <div class="register-card">
        <div class="register-header">
            <img src="assets/images/logo.png" alt="Parking SV" class="register-logo">
            <h1 class="register-title">Crear cuenta</h1>
            <p class="register-subtitle">Únete a Parking SV y comienza a gestionar tus estacionamientos</p>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $errors['general']; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="register-form" id="registerForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Nombre completo -->
            <div class="form-group <?php echo isset($errors['full_name']) ? 'has-error' : ''; ?>">
                <label for="full_name" class="form-label">
                    <i class="fas fa-user"></i>
                    Nombre completo
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-input" 
                        placeholder="Juan Pérez"
                        value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>"
                        required
                    >
                </div>
                <?php if (isset($errors['full_name'])): ?>
                    <span class="error-message"><?php echo $errors['full_name']; ?></span>
                <?php endif; ?>
            </div>

            <!-- Tipo de usuario -->
            <div class="form-group <?php echo isset($errors['user_type']) ? 'has-error' : ''; ?>">
                <label class="form-label">
                    <i class="fas fa-users"></i>
                    Tipo de usuario
                </label>
                <div class="user-type-selection">
                    <input type="radio" id="customer" name="user_type" value="customer" 
                        <?php echo ($form_data['user_type'] ?? '') === 'customer' ? 'checked' : ''; ?>>
                    <label for="customer" class="user-type-card">
                        <i class="fas fa-car"></i>
                        <span class="user-type-title">Cliente</span>
                        <span class="user-type-desc">Busco estacionamiento</span>
                    </label>

                    <input type="radio" id="owner" name="user_type" value="owner"
                        <?php echo ($form_data['user_type'] ?? '') === 'owner' ? 'checked' : ''; ?>>
                    <label for="owner" class="user-type-card">
                        <i class="fas fa-building"></i>
                        <span class="user-type-title">Propietario</span>
                        <span class="user-type-desc">Ofrezco estacionamiento</span>
                    </label>
                </div>
                <?php if (isset($errors['user_type'])): ?>
                    <span class="error-message"><?php echo $errors['user_type']; ?></span>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="form-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i>
                    Correo electrónico
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="tu@ejemplo.com"
                        value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                        required
                    >
                </div>
                <?php if (isset($errors['email'])): ?>
                    <span class="error-message"><?php echo $errors['email']; ?></span>
                <?php endif; ?>
            </div>

            <!-- Contraseña -->
            <div class="form-group <?php echo isset($errors['password']) ? 'has-error' : ''; ?>">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i>
                    Contraseña
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••"
                        required
                    >
                    <button type="button" class="toggle-password" data-target="password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
                <?php if (isset($errors['password'])): ?>
                    <span class="error-message"><?php echo $errors['password']; ?></span>
                <?php endif; ?>
            </div>

            <!-- Confirmar contraseña -->
            <div class="form-group <?php echo isset($errors['confirm_password']) ? 'has-error' : ''; ?>">
                <label for="confirm_password" class="form-label">
                    <i class="fas fa-lock"></i>
                    Confirmar contraseña
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input" 
                        placeholder="••••••••"
                        required
                    >
                    <button type="button" class="toggle-password" data-target="confirm_password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                    <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                <?php endif; ?>
            </div>

            <!-- Términos y condiciones -->
            <div class="form-group checkbox-group <?php echo isset($errors['terms']) ? 'has-error' : ''; ?>">
                <label class="checkbox-label">
                    <input 
                        type="checkbox" 
                        id="terms_accepted" 
                        name="terms_accepted"
                        <?php echo ($form_data['terms_accepted'] ?? false) ? 'checked' : ''; ?>
                    >
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-text">
                        Acepto los <a href="terms.php" target="_blank">términos y condiciones</a>
                    </span>
                </label>
                <?php if (isset($errors['terms'])): ?>
                    <span class="error-message"><?php echo $errors['terms']; ?></span>
                <?php endif; ?>
            </div>

            <!-- Botón de registro -->
            <button type="submit" class="btn-register" id="submitBtn">
                <span class="btn-text">Crear cuenta</span>
                <span class="btn-loader" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>
        </form>

        <div class="register-footer">
            <p>¿Ya tienes cuenta? <a href="login.php" class="login-link">Inicia sesión</a></p>
        </div>
    </div>
</div>

<script src="assets/js/pages/register.js"></script>

<?php include 'includes/footer.php'; ?>