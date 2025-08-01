<?php
/**
 * Página principal - Redirección automática
 * Sistema de Login Dinámico con Roles
 */

// Incluir configuración
require_once 'config.php';
require_once 'inc/functions.php';

// Verificar si el usuario está logueado
if (isLoggedIn()) {
    // Si está logueado, redirigir al dashboard
    redirect('dashboard.php');
} else {
    // Si no está logueado, redirigir al login
    redirect('login.php');
}
?>
