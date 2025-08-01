<?php
/**
 * Funciones comunes del sistema
 * Sistema de Login Dinámico con Roles
 */

/**
 * Función para redireccionar
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Función para limpiar datos de entrada
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Verificar si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verificar rol del usuario
 */
function checkUserRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if ($required_role === 'admin' && $_SESSION['user_role'] !== 'admin') {
        return false;
    }
    
    return true;
}

/**
 * Proteger página - requiere login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * Proteger página - requiere rol específico
 */
function requireRole($role) {
    requireLogin();
    if (!checkUserRole($role)) {
        redirect('dashboard.php?error=access_denied');
    }
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generar hash de contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verificar contraseña
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Mostrar mensaje de alerta
 */
function showAlert($message, $type = 'info') {
    $alertClass = '';
    switch ($type) {
        case 'success':
            $alertClass = 'alert-success';
            break;
        case 'error':
            $alertClass = 'alert-danger';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            break;
        default:
            $alertClass = 'alert-info';
    }
    
    return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Formatear fecha
 */
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}
?>
