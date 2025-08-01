<?php
/**
 * Cerrar Sesión DEMO
 * Sistema de Login Dinámico con Roles
 */

// Incluir configuración demo
require_once 'config_demo.php';
require_once 'inc/functions.php';

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login demo con mensaje
redirect('login_demo.php?message=logout_success');
?>
