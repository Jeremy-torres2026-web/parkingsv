<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Incluimos manualmente las clases necesarias
require __DIR__ . '/src/Exception.php';
require __DIR__ . '/src/PHPMailer.php';
require __DIR__ . '/src/SMTP.php';

/**
 * Función general para enviar emails con PHPMailer
 * 
 * @param string $toEmail Email del destinatario
 * @param string $toName Nombre del destinatario
 * @param string $subject Asunto del email
 * @param string $body Cuerpo del email en HTML
 * @return bool True si se envió correctamente, False en caso contrario
 */
function send_email($toEmail, $toName, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'elalienjeremy@gmail.com';       // Tu correo de Gmail
        $mail->Password = 'xuka sdqb oibb cssm';            // Tu contraseña de aplicación
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Dirección y contenido del mensaje
        $mail->setFrom('elalienjeremy@gmail.com', 'Parking SV');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Versión texto plano (fallback)
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Función específica para enviar código de verificación
 * (Mantiene compatibilidad con código existente)
 * 
 * @param string $toEmail Email del destinatario
 * @param string $toName Nombre del destinatario
 * @param string $code Código de verificación de 6 dígitos
 * @return bool True si se envió correctamente, False en caso contrario
 */
function sendVerificationEmail($toEmail, $toName, $code) {
    $subject = 'Tu código de verificación - Parking SV';
    $verifyLink = "http://localhost/crud-php2/verify-email.php?email=$toEmail&code=$code";
    
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #0C6FF9;'>Verifica tu cuenta</h2>
            <p>Hola <strong>{$toName}</strong>,</p>
            <p>Tu código de verificación es:</p>
            <div style='background: #f5f7fa; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                <h1 style='color: #0C6FF9; font-size: 32px; letter-spacing: 5px; margin: 0;'>{$code}</h1>
            </div>
            <p>O verifica directamente haciendo clic aquí:</p>
            <a href='{$verifyLink}' style='display: inline-block; padding: 12px 24px; background: #0C6FF9; color: white; text-decoration: none; border-radius: 6px;'>Confirmar correo</a>
            <p style='margin-top: 20px;'>Este código vence en <strong>10 minutos</strong> y solo puedes intentar 3 veces.</p>
            <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
            <p style='color: #666; font-size: 12px;'>Parking SV - Sistema de Gestión de Estacionamientos</p>
        </div>
    ";
    
    return send_email($toEmail, $toName, $subject, $body);
}