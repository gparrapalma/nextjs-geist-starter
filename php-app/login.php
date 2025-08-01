<?php
/**
 * Página de Login
 * Sistema de Login Dinámico con Roles
 */

// Incluir configuración
require_once 'config.php';
require_once 'inc/functions.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            // Buscar usuario en la base de datos
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Registrar actividad
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $user['id'],
                    'login',
                    'Usuario inició sesión',
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                redirect('dashboard.php');
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error en el sistema. Intente nuevamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/adminlte-custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <img src="https://placehold.co/80x80?text=Sistema+Login+Logo+Moderno" alt="Logo del sistema de login moderno y elegante" onerror="this.style.display='none';" style="width: 60px; height: 60px; margin-bottom: 10px; border-radius: 50%;">
            <h1><?php echo SITE_NAME; ?></h1>
        </div>
        
        <div class="login-card-body">
            <p class="login-box-msg">Inicie sesión para acceder al sistema</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           placeholder="Ingrese su usuario"
                           value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Ingrese su contraseña"
                           required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Iniciar Sesión
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <small style="color: #666;">
                    <strong>Usuarios de prueba:</strong><br>
                    Admin: admin / admin123<br>
                    Editor: editor / editor123<br>
                    Usuario: usuario / user123
                </small>
            </div>
        </div>
    </div>
    
    <!-- Imagen de fondo decorativa -->
    <div style="position: fixed; bottom: 20px; right: 20px; opacity: 0.1; z-index: -1;">
        <img src="https://placehold.co/400x300?text=Fondo+decorativo+sistema+administrativo+moderno" alt="Fondo decorativo del sistema administrativo moderno" onerror="this.style.display='none';" style="width: 300px; height: auto;">
    </div>
    
    <script src="assets/js/adminlte-custom.js"></script>
</body>
</html>
