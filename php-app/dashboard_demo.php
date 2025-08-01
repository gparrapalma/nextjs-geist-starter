<?php
/**
 * Dashboard Principal DEMO
 * Sistema de Login Dinámico con Roles
 */

// Incluir configuración demo
require_once 'config_demo.php';
require_once 'inc/functions.php';

// Verificar que el usuario esté logueado
requireLogin();

// Obtener información del usuario actual
$currentUser = getCurrentUser($pdo);

// Obtener estadísticas básicas
try {
    // Total de usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $totalUsers = $stmt->fetch()['total'];
    
    // Usuarios por rol
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY role");
    $usersByRole = $stmt->fetchAll();
    
    // Actividad reciente (últimos 10 registros)
    $stmt = $pdo->prepare("
        SELECT al.*, u.username, u.full_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivity = $stmt->fetchAll();
    
} catch (Exception $e) {
    $totalUsers = 3;
    $usersByRole = [
        ['role' => 'admin', 'count' => 1],
        ['role' => 'editor', 'count' => 1],
        ['role' => 'user', 'count' => 1]
    ];
    $recentActivity = [];
}

// Mensajes de alerta
$message = '';
$messageType = '';

if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'user_created':
            $message = 'Usuario creado exitosamente.';
            $messageType = 'success';
            break;
        case 'user_updated':
            $message = 'Usuario actualizado exitosamente.';
            $messageType = 'success';
            break;
        case 'user_deleted':
            $message = 'Usuario eliminado exitosamente.';
            $messageType = 'success';
            break;
        case 'access_denied':
            $message = 'No tiene permisos para acceder a esa sección.';
            $messageType = 'warning';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard DEMO - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/adminlte-custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Header -->
        <header class="main-header">
            <nav class="navbar-nav">
                <div>
                    <h1><?php echo SITE_NAME; ?></h1>
                </div>
                <div class="user-info">
                    Bienvenido, <strong><?php echo htmlspecialchars($currentUser['full_name']); ?></strong> 
                    (<?php echo ucfirst($currentUser['role']); ?>) | 
                    <a href="logout_demo.php" style="color: #fff; text-decoration: underline;">Cerrar Sesión</a>
                </div>
            </nav>
        </header>

        <!-- Sidebar -->
        <aside class="main-sidebar">
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard_demo.php" class="active">
                        Dashboard
                    </a>
                </li>
                <?php if (checkUserRole('admin')): ?>
                <li>
                    <a href="user_list_demo.php">
                        Gestión de Usuarios
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="#" onclick="alert('Función disponible en versión completa')">
                        Mi Perfil
                    </a>
                </li>
                <li>
                    <a href="logout_demo.php">
                        Cerrar Sesión
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Banner DEMO -->
            <div style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <h3 style="margin: 0; font-size: 18px;">🎯 MODO DEMOSTRACIÓN</h3>
                <p style="margin: 5px 0 0 0; font-size: 14px;">
                    Este es un sistema de demostración que funciona sin base de datos. 
                    <strong>Todas las funcionalidades están disponibles en la versión completa.</strong>
                </p>
            </div>

            <?php if ($message): ?>
                <?php echo showAlert($message, $messageType); ?>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Estadísticas del Sistema</h3>
                </div>
                <div class="box-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div style="background: linear-gradient(135deg, #3c8dbc, #367fa9); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                            <h3 style="margin: 0; font-size: 2em;"><?php echo $totalUsers; ?></h3>
                            <p style="margin: 5px 0 0 0;">Usuarios Activos</p>
                        </div>
                        
                        <?php foreach ($usersByRole as $roleData): ?>
                        <div style="background: linear-gradient(135deg, #00a65a, #008d4c); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                            <h3 style="margin: 0; font-size: 2em;"><?php echo $roleData['count']; ?></h3>
                            <p style="margin: 5px 0 0 0;"><?php echo ucfirst($roleData['role']); ?>s</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Información del Usuario Actual -->
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Mi Información</h3>
                </div>
                <div class="box-body">
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: center;">
                        <div style="text-align: center;">
                            <img src="https://placehold.co/150x150?text=Avatar+Usuario+<?php echo substr($currentUser['full_name'], 0, 1); ?>" 
                                 alt="Avatar del usuario <?php echo htmlspecialchars($currentUser['full_name']); ?>" 
                                 style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid #3c8dbc;"
                                 onerror="this.src='https://placehold.co/150x150?text=User';">
                        </div>
                        <div>
                            <table class="table">
                                <tr>
                                    <td><strong>Nombre Completo:</strong></td>
                                    <td><?php echo htmlspecialchars($currentUser['full_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Usuario:</strong></td>
                                    <td><?php echo htmlspecialchars($currentUser['username']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($currentUser['email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Rol:</strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo $currentUser['role'] == 'admin' ? 'danger' : ($currentUser['role'] == 'editor' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($currentUser['role']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <span class="badge badge-success">
                                            <?php echo ucfirst($currentUser['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Registrado:</strong></td>
                                    <td><?php echo formatDate($currentUser['created_at']); ?></td>
                                </tr>
                            </table>
                            <div class="mt-3">
                                <button onclick="alert('Función disponible en versión completa')" class="btn btn-primary">
                                    Editar Mi Perfil
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Funcionalidades Demo -->
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Funcionalidades del Sistema</h3>
                </div>
                <div class="box-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                            <h4 style="color: #3c8dbc; margin-bottom: 10px;">🔐 Sistema de Autenticación</h4>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li>Login seguro con contraseñas hasheadas</li>
                                <li>Gestión de sesiones</li>
                                <li>Validación de formularios</li>
                                <li>Protección contra ataques</li>
                            </ul>
                        </div>
                        
                        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                            <h4 style="color: #00a65a; margin-bottom: 10px;">👥 Gestión de Usuarios</h4>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li>CRUD completo de usuarios</li>
                                <li>Roles diferenciados (Admin, Editor, Usuario)</li>
                                <li>Filtros y búsqueda avanzada</li>
                                <li>Control de permisos</li>
                            </ul>
                        </div>
                        
                        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                            <h4 style="color: #f39c12; margin-bottom: 10px;">🎨 Diseño AdminLTE</h4>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li>Interfaz moderna y responsive</li>
                                <li>Sidebar navegable</li>
                                <li>Dashboard con estadísticas</li>
                                <li>Diseño limpio y profesional</li>
                            </ul>
                        </div>
                        
                        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                            <h4 style="color: #e74c3c; margin-bottom: 10px;">🗄️ Base de Datos MySQL</h4>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li>Estructura completa de tablas</li>
                                <li>Logs de actividad</li>
                                <li>Consultas optimizadas</li>
                                <li>Instalador automático</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accesos Rápidos -->
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Accesos Rápidos</h3>
                </div>
                <div class="box-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <button onclick="alert('Función disponible en versión completa')" class="btn btn-primary" style="padding: 20px; text-align: center;">
                            Editar Mi Perfil
                        </button>
                        
                        <?php if (checkUserRole('admin')): ?>
                        <a href="user_list_demo.php" class="btn btn-success" style="padding: 20px; text-align: center; text-decoration: none;">
                            Gestionar Usuarios
                        </a>
                        
                        <button onclick="alert('Función disponible en versión completa')" class="btn btn-warning" style="padding: 20px; text-align: center;">
                            Crear Nuevo Usuario
                        </button>
                        <?php endif; ?>
                        
                        <a href="logout_demo.php" class="btn btn-secondary" style="padding: 20px; text-align: center; text-decoration: none;">
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>

            <!-- Información adicional -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-top: 20px; text-align: center;">
                <h4 style="color: #666; margin-bottom: 10px;">💡 ¿Quiere la versión completa?</h4>
                <p style="color: #666; margin-bottom: 15px;">
                    Esta demostración muestra las principales funcionalidades del sistema. 
                    La versión completa incluye base de datos MySQL, instalador automático y todas las características avanzadas.
                </p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <button onclick="alert('Contacte al desarrollador para obtener la versión completa')" class="btn btn-primary">
                        Obtener Versión Completa
                    </button>
                    <button onclick="window.open('README.md', '_blank')" class="btn btn-secondary">
                        Ver Documentación
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/adminlte-custom.js"></script>
</body>
</html>
