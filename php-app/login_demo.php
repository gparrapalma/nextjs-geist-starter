<?php
/**
 * Página de Login DEMO
 * Sistema de Login Dinámico con Roles
 */

// Incluir configuración demo
require_once 'config_demo.php';
require_once 'inc/functions.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect('dashboard_demo.php');
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
            // Buscar usuario en la base de datos demo
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                redirect('dashboard_demo.php');
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (Exception $e) {
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
    <title>Login DEMO - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/adminlte-custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <img src="https://placehold.co/80x80?text=DEMO+Sistema+Login+Logo+Moderno" alt="Logo del sistema de login DEMO moderno y elegante" onerror="this.style.display='none';" style="width: 60px; height: 60px; margin-bottom: 10px; border-radius: 50%;">
            <h1><?php echo SITE_NAME; ?></h1>
        </div>
        
        <div class="login-card-body">
            <p class="login-box-msg">Inicie sesión para acceder al sistema DEMO</p>
            
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
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <strong style="color: #e74c3c;">🎯 MODO DEMO</strong><br>
                    <small style="color: #666;">
                        Este es un sistema de demostración que funciona sin base de datos.
                    </small>
                </div>
                
                <small style="color: #666;">
                    <strong>Usuarios de prueba:</strong><br>
                    <strong>Admin:</strong> admin / admin123<br>
                    <strong>Editor:</strong> editor / editor123<br>
                    <strong>Usuario:</strong> usuario / user123
                </small>
            </div>
        </div>
    </div>
    
    <!-- Imagen de fondo decorativa -->
    <div style="position: fixed; bottom: 20px; right: 20px; opacity: 0.1; z-index: -1;">
        <img src="https://placehold.co/400x300?text=Fondo+decorativo+sistema+administrativo+DEMO" alt="Fondo decorativo del sistema administrativo DEMO" onerror="this.style.display='none';" style="width: 300px; height: auto;">
    </div>
    
    <script src="assets/js/adminlte-custom.js"></script>
</body>
</html>
