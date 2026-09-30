<?php
require __DIR__ . '/libs/PHPMailer/mailer.php';

if (sendVerificationEmail('jeremyensuprime@gmail.com', 'Usuario', rand(100000, 999999))) {
    echo "Correo enviado correctamente.";
} else {
    echo "Error al enviar el correo.";
}
