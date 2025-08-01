<?php
/**
 * Cerrar Sesión
 * Sistema de Login Dinámico con Roles
 */

// Incluir configuración
require_once 'config.php';
require_once 'inc/functions.php';

// Registrar actividad de logout si hay sesión activa
if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            'logout',
            'Usuario cerró sesión',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Error silencioso en el log
    }
}

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

// Redirigir al login con mensaje
redirect('login.php?message=logout_success');
?>
