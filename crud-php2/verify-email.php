<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'conexion.php';
require_once 'libs/PHPMailer/mailer.php';

// Verificar que el usuario tenga una verificación pendiente
if (!isset($_SESSION['pending_verification_email']) || !isset($_SESSION['verification_user_id'])) {
    header('Location: login.php');
    exit();
}

$email = $_SESSION['pending_verification_email'];
$user_id = $_SESSION['verification_user_id'];
$errors = [];
$success = false;
$resend_success = false;

// Función para verificar si el código ha expirado (10 minutos)
function is_code_expired($conex, $email) {
    $email_escaped = mysqli_real_escape_string($conex, $email);
    $query = "SELECT created_at FROM verifications 
              WHERE email = '$email_escaped' 
              ORDER BY created_at DESC LIMIT 1";
    $result = mysqli_query($conex, $query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $created_time = strtotime($row['created_at']);
        $current_time = time();
        $diff_minutes = ($current_time - $created_time) / 60;
        return $diff_minutes > 10;
    }
    return true;
}

// Función para obtener el número de intentos
function get_attempts($conex, $email) {
    $email_escaped = mysqli_real_escape_string($conex, $email);
    $query = "SELECT attempts FROM verifications 
              WHERE email = '$email_escaped' 
              ORDER BY created_at DESC LIMIT 1";
    $result = mysqli_query($conex, $query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return (int)$row['attempts'];
    }
    return 0;
}

// Manejar reenvío de código
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    try {
        // Generar nuevo código
        $new_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $email_escaped = mysqli_real_escape_string($conex, $email);
        
        // Eliminar códigos antiguos y crear uno nuevo
        $delete_query = "DELETE FROM verifications WHERE email = '$email_escaped'";
        mysqli_query($conex, $delete_query);
        
        $insert_query = "INSERT INTO verifications (email, code, created_at, attempts) 
                        VALUES ('$email_escaped', '$new_code', NOW(), 0)";
        mysqli_query($conex, $insert_query);
        
        // Obtener nombre del usuario
        $user_query = "SELECT full_name FROM users WHERE id = $user_id";
        $user_result = mysqli_query($conex, $user_query);
        $user_data = mysqli_fetch_assoc($user_result);
        $full_name = $user_data['full_name'];
        
        // Enviar nuevo email
        $email_subject = 'Nuevo código de verificación - Parking SV';
        $email_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #0C6FF9;'>Nuevo código de verificación</h2>
                <p>Hola <strong>{$full_name}</strong>,</p>
                <p>Has solicitado un nuevo código de verificación:</p>
                <div style='background: #f5f7fa; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                    <h1 style='color: #0C6FF9; font-size: 32px; letter-spacing: 5px; margin: 0;'>{$new_code}</h1>
                </div>
                <p>Este código expirará en <strong>10 minutos</strong>.</p>
                <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                <p style='color: #666; font-size: 12px;'>Parking SV - Sistema de Gestión de Estacionamientos</p>
            </div>
        ";
        
        if (send_email($email, $full_name, $email_subject, $email_body)) {
            $resend_success = true;
        } else {
            $errors['general'] = 'Error al enviar el código. Intenta nuevamente.';
        }
        
    } catch (Exception $e) {
        $errors['general'] = 'Error al reenviar el código: ' . $e->getMessage();
    }
}

// Manejar verificación de código
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $code = sanitize_input($_POST['code'] ?? '');
    
    // Validar que el código tenga 6 dígitos
    if (empty($code)) {
        $errors['code'] = 'Por favor, ingresa el código de verificación.';
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $errors['code'] = 'El código debe tener 6 dígitos.';
    } else {
        // Verificar si el código ha expirado
        if (is_code_expired($conex, $email)) {
            $errors['code'] = 'El código ha expirado. Solicita uno nuevo.';
        } else {
            // Verificar intentos
            $attempts = get_attempts($conex, $email);
            
            if ($attempts >= 3) {
                $errors['code'] = 'Has superado el número máximo de intentos. Solicita un nuevo código.';
            } else {
                // Verificar el código
                $email_escaped = mysqli_real_escape_string($conex, $email);
                $code_escaped = mysqli_real_escape_string($conex, $code);
                
                $query = "SELECT * FROM verifications 
                         WHERE email = '$email_escaped' 
                         AND code = '$code_escaped'
                         ORDER BY created_at DESC LIMIT 1";
                $result = mysqli_query($conex, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    // Código correcto - activar usuario
                    mysqli_begin_transaction($conex);
                    
                    try {
                        // Actualizar usuario como verificado
                        $update_user = "UPDATE users 
                                       SET email_verified = 1, 
                                           email_verified_at = NOW() 
                                       WHERE id = $user_id";
                        mysqli_query($conex, $update_user);
                        
                        // Eliminar código de verificación
                        $delete_verification = "DELETE FROM verifications 
                                               WHERE email = '$email_escaped'";
                        mysqli_query($conex, $delete_verification);
                        
                        mysqli_commit($conex);
                        
                        // Limpiar sesión de verificación
                        unset($_SESSION['pending_verification_email']);
                        unset($_SESSION['verification_user_id']);
                        
                        // Establecer sesión del usuario
                        $_SESSION['user_id'] = $user_id;
                        
                        $success = true;
                        
                        // Redirigir después de 2 segundos
                        header("refresh:2;url=index.php");
                        
                    } catch (Exception $e) {
                        mysqli_rollback($conex);
                        $errors['general'] = 'Error al verificar la cuenta. Intenta nuevamente.';
                    }
                } else {
                    // Código incorrecto - incrementar intentos
                    $update_attempts = "UPDATE verifications 
                                       SET attempts = attempts + 1 
                                       WHERE email = '$email_escaped'";
                    mysqli_query($conex, $update_attempts);
                    
                    $remaining = 3 - ($attempts + 1);
                    if ($remaining > 0) {
                        $errors['code'] = "Código incorrecto. Te quedan $remaining intento(s).";
                    } else {
                        $errors['code'] = 'Has superado el número máximo de intentos. Solicita un nuevo código.';
                    }
                }
            }
        }
    }
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Obtener información para mostrar
$attempts = get_attempts($conex, $email);
$is_expired = is_code_expired($conex, $email);
$remaining_attempts = max(0, 3 - $attempts);
?>
<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="assets/css/pages/verify-email.css">

<div class="verify-container">
    <div class="verify-card">
        <div class="verify-header">
            <div class="icon-container">
                <?php if ($success): ?>
                    <i class="fas fa-check-circle success-icon"></i>
                <?php else: ?>
                    <i class="fas fa-envelope-open-text"></i>
                <?php endif; ?>
            </div>
            <h1 class="verify-title">
                <?php echo $success ? '¡Cuenta Verificada!' : 'Verifica tu correo'; ?>
            </h1>
            <p class="verify-subtitle">
                <?php if ($success): ?>
                    Tu cuenta ha sido activada exitosamente.
                <?php else: ?>
                    Hemos enviado un código de 6 dígitos a:<br>
                    <strong><?php echo htmlspecialchars($email); ?></strong>
                <?php endif; ?>
            </p>
        </div>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-rocket"></i>
                <p>Redirigiendo al inicio...</p>
            </div>
        <?php else: ?>
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $errors['general']; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($resend_success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>¡Código reenviado exitosamente! Revisa tu bandeja de entrada.</span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="verify-form" id="verifyForm">
                <div class="form-group <?php echo isset($errors['code']) ? 'has-error' : ''; ?>">
                    <label for="code" class="form-label">
                        <i class="fas fa-key"></i>
                        Código de verificación
                    </label>
                    <div class="code-input-container" id="codeInputContainer">
                        <input type="text" maxlength="1" class="code-digit" data-index="0" autocomplete="off">
                        <input type="text" maxlength="1" class="code-digit" data-index="1" autocomplete="off">
                        <input type="text" maxlength="1" class="code-digit" data-index="2" autocomplete="off">
                        <input type="text" maxlength="1" class="code-digit" data-index="3" autocomplete="off">
                        <input type="text" maxlength="1" class="code-digit" data-index="4" autocomplete="off">
                        <input type="text" maxlength="1" class="code-digit" data-index="5" autocomplete="off">
                    </div>
                    <input type="hidden" name="code" id="codeHidden">
                    <?php if (isset($errors['code'])): ?>
                        <span class="error-message"><?php echo $errors['code']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="info-section">
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span>El código expira en 10 minutos</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Intentos restantes: <strong><?php echo $remaining_attempts; ?>/3</strong></span>
                    </div>
                </div>

                <button type="submit" name="verify_code" class="btn-verify" id="verifyBtn">
                    <span class="btn-text">Verificar código</span>
                    <span class="btn-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="resend-section">
                <p>¿No recibiste el código?</p>
                <form method="POST" action="" style="display: inline;">
                    <button type="submit" name="resend_code" class="btn-resend" id="resendBtn">
                        <i class="fas fa-paper-plane"></i>
                        Reenviar código
                    </button>
                </form>
            </div>

            <div class="help-section">
                <p class="help-text">
                    <i class="fas fa-info-circle"></i>
                    Revisa tu carpeta de spam si no encuentras el correo
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/pages/verify-email.js"></script>

<?php include 'includes/footer.php'; ?>