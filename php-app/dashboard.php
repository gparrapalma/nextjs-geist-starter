<?php
/**
 * Dashboard Principal
 * Sistema de Login Dinámico con Roles
 */

// Incluir configuración
require_once 'config.php';
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
    
} catch (PDOException $e) {
    $totalUsers = 0;
    $usersByRole = [];
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
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
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
                    <a href="logout.php" style="color: #fff; text-decoration: underline;">Cerrar Sesión</a>
                </div>
            </nav>
        </header>

        <!-- Sidebar -->
        <aside class="main-sidebar">
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        Dashboard
                    </a>
                </li>
                <?php if (checkUserRole('admin')): ?>
                <li>
                    <a href="user_list.php">
                        Gestión de Usuarios
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="user_edit.php?id=<?php echo $currentUser['id']; ?>">
                        Mi Perfil
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        Cerrar Sesión
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
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
                                <a href="user_edit.php?id=<?php echo $currentUser['id']; ?>" class="btn btn-primary">
                                    Editar Mi Perfil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actividad Reciente -->
            <?php if (checkUserRole('admin')): ?>
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Actividad Reciente del Sistema</h3>
                </div>
                <div class="box-body">
                    <?php if (empty($recentActivity)): ?>
                        <p>No hay actividad reciente registrada.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Descripción</th>
                                    <th>Fecha</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivity as $activity): ?>
                                <tr>
                                    <td>
                                        <?php echo $activity['full_name'] ? htmlspecialchars($activity['full_name']) : 'Usuario eliminado'; ?>
                                        <?php if ($activity['username']): ?>
                                            <small style="color: #666;">(<?php echo htmlspecialchars($activity['username']); ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $activity['action'] == 'login' ? 'success' : ($activity['action'] == 'logout' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($activity['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                    <td><?php echo formatDate($activity['created_at']); ?></td>
                                    <td><small><?php echo htmlspecialchars($activity['ip_address']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Accesos Rápidos -->
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Accesos Rápidos</h3>
                </div>
                <div class="box-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="user_edit.php?id=<?php echo $currentUser['id']; ?>" class="btn btn-primary" style="padding: 20px; text-align: center; text-decoration: none;">
                            Editar Mi Perfil
                        </a>
                        
                        <?php if (checkUserRole('admin')): ?>
                        <a href="user_list.php" class="btn btn-success" style="padding: 20px; text-align: center; text-decoration: none;">
                            Gestionar Usuarios
                        </a>
                        
                        <a href="user_edit.php" class="btn btn-warning" style="padding: 20px; text-align: center; text-decoration: none;">
                            Crear Nuevo Usuario
                        </a>
                        <?php endif; ?>
                        
                        <a href="logout.php" class="btn btn-secondary" style="padding: 20px; text-align: center; text-decoration: none;">
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/adminlte-custom.js"></script>
</body>
</html>
