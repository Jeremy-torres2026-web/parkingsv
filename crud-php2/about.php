<?php
session_start();
$page_title = "Parking SV - Sobre Nosotros";
include 'includes/header.php';
include 'conexion.php';
if (isset($_SESSION['mensaje'])) {
    echo $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}
?>
    <!-- Fuente y Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pages/about.css">

    <!-- QUIÉNES SOMOS -->
    <section class="section about card">
        <h2><span style="color:#007bff;">¿Quiénes</span> somos?</h2>
        <p class="text">
            Somos un grupo de jóvenes comprometidos con impulsar el cambio en nuestra comunidad y 
            contribuir al crecimiento sostenible de El Salvador. Nuestra meta es utilizar la tecnología 
            como herramienta para resolver problemas reales, promoviendo soluciones innovadoras que 
            mejoren la calidad de vida de las personas y fomenten un entorno más ordenado, seguro y conectado.
        </p>
        <!-- Imagen corregida rectangular -->
        <img src="img sources/grupo1.jpg" alt="Foto del equipo" class="equipo-img">
    </section>

    <!-- MISIÓN Y VISIÓN -->
    <section class="section mission-vision">
        <div class="card">
            <i class="fas fa-bullseye"></i>
            <h3>¡Misión!</h3>
            <p>Facilitar la movilidad urbana con una plataforma segura para reservar y compartir parques, optimizando recursos y reduciendo tráfico y contaminación.</p>
        </div>
        <div class="card">
            <i class="fas fa-lightbulb"></i>
            <h3>¡Visión!</h3>
            <p>Ser la solución líder en gestión inteligente de parques, creando comunidades sostenibles y conectadas con acceso disponible en todo momento y lugar.</p>
        </div>
    </section>

    <!-- CONTACTO -->
    <section class="section card">
        <h3>¡Contáctanos!</h3>
        <div class="contact-info">
            <a href="tel:+50369344318"><i class="fas fa-phone"></i> +503 6934 4318</a>
            <a href="https://instagram.com/ParkingSV" target="_blank"><i class="fab fa-instagram"></i> @ParkingSV</a>
            <a href="mailto:parkingsv@gmail.com"><i class="fas fa-envelope"></i> parkingsv@gmail.com</a>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
