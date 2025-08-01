<?php
/**
 * Configuración de la base de datos y configuraciones globales
 * Sistema de Login Dinámico con Roles
 */

// Configuración de la base de datos MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'login_app');
define('DB_USER', 'root');  // Cambiar por tu usuario de MySQL
define('DB_PASS', '');      // Cambiar por tu contraseña de MySQL

// Configuraciones globales
define('SITE_NAME', 'Sistema de Login Dinámico');
define('SITE_URL', 'http://localhost/php-app');

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos usando PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Zona horaria
date_default_timezone_set('America/Santiago');
?>
