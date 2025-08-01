<?php
/**
 * Lista de Usuarios - Solo Administradores
 * Sistema de Login Dinámico con Roles
 */

// Incluir configuración
require_once 'config.php';
require_once 'inc/functions.php';

// Verificar que el usuario esté logueado y sea administrador
requireRole('admin');

// Obtener información del usuario actual
$currentUser = getCurrentUser($pdo);

// Procesar eliminación de usuario
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    // No permitir que el admin se elimine a sí mismo
    if ($userId == $currentUser['id']) {
        redirect('user_list.php?message=cannot_delete_self');
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Registrar actividad
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $currentUser['id'],
            'delete_user',
            "Usuario eliminado (ID: $userId)",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        redirect('user_list.php?message=user_deleted');
    } catch (PDOException $e) {
        redirect('user_list.php?message=delete_error');
    }
}

// Obtener lista de usuarios con filtros
$search = clean_input($_GET['search'] ?? '');
$roleFilter = clean_input($_GET['role'] ?? '');
$statusFilter = clean_input($_GET['status'] ?? '');

$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($roleFilter)) {
    $whereConditions[] = "role = ?";
    $params[] = $roleFilter;
}

if (!empty($statusFilter)) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users $whereClause ORDER BY created_at DESC");
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

// Mensajes de alerta
$message = '';
$messageType = '';

if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'user_deleted':
            $message = 'Usuario eliminado exitosamente.';
            $messageType = 'success';
            break;
        case 'cannot_delete_self':
            $message = 'No puede eliminar su propia cuenta.';
            $messageType = 'warning';
            break;
        case 'delete_error':
            $message = 'Error al eliminar el usuario.';
            $messageType = 'error';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?php echo SITE_NAME; ?></title>
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
                    <a href="dashboard.php">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="user_list.php" class="active">
                        Gestión de Usuarios
                    </a>
                </li>
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

            <!-- Filtros y búsqueda -->
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Filtros de Búsqueda</h3>
                </div>
                <div class="box-body">
                    <form method="GET" action="">
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                            <div class="form-group">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" 
                                       id="search" 
                                       name="search" 
                                       class="form-control" 
                                       placeholder="Buscar por usuario, nombre o email"
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="role" class="form-label">Rol</label>
                                <select id="role" name="role" class="form-control form-select">
                                    <option value="">Todos los roles</option>
                                    <option value="admin" <?php echo $roleFilter == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    <option value="editor" <?php echo $roleFilter == 'editor' ? 'selected' : ''; ?>>Editor</option>
                                    <option value="user" <?php echo $roleFilter == 'user' ? 'selected' : ''; ?>>Usuario</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status" class="form-label">Estado</label>
                                <select id="status" name="status" class="form-control form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactive" <?php echo $statusFilter == 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="user_list.php" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de usuarios -->
            <div class="box">
                <div class="box-header d-flex justify-content-between align-items-center">
                    <h3 class="box-title">Lista de Usuarios (<?php echo count($users); ?> usuarios)</h3>
                    <a href="user_edit.php" class="btn btn-success">Crear Nuevo Usuario</a>
                </div>
                <div class="box-body">
                    <?php if (empty($users)): ?>
                        <div class="text-center" style="padding: 40px;">
                            <img src="https://placehold.co/200x150?text=No+hay+usuarios+encontrados" 
                                 alt="No hay usuarios encontrados en el sistema" 
                                 style="opacity: 0.5; margin-bottom: 20px;"
                                 onerror="this.style.display='none';">
                            <p style="color: #666; font-size: 16px;">No se encontraron usuarios con los filtros aplicados.</p>
                            <a href="user_edit.php" class="btn btn-primary">Crear Primer Usuario</a>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Avatar</th>
                                        <th>Usuario</th>
                                        <th>Nombre Completo</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Registrado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <img src="https://placehold.co/40x40?text=<?php echo substr($user['full_name'], 0, 1); ?>" 
                                                 alt="Avatar de <?php echo htmlspecialchars($user['full_name']); ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid #ddd;"
                                                 onerror="this.src='https://placehold.co/40x40?text=U';">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if ($user['id'] == $currentUser['id']): ?>
                                                <small style="color: #3c8dbc;">(Tú)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'editor' ? 'warning' : 'info'); ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo formatDate($user['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <a href="user_edit.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-warning" 
                                               style="padding: 5px 10px; font-size: 12px;">
                                                Editar
                                            </a>
                                            
                                            <?php if ($user['id'] != $currentUser['id']): ?>
                                                <a href="user_list.php?delete=<?php echo $user['id']; ?>" 
                                                   class="btn btn-danger btn-delete" 
                                                   style="padding: 5px 10px; font-size: 12px;"
                                                   onclick="return confirm('¿Está seguro de que desea eliminar este usuario?');">
                                                    Eliminar
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Estadísticas Rápidas</h3>
                </div>
                <div class="box-body">
                    <?php
                    $stats = [
                        'total' => count($users),
                        'admin' => count(array_filter($users, function($u) { return $u['role'] == 'admin'; })),
                        'editor' => count(array_filter($users, function($u) { return $u['role'] == 'editor'; })),
                        'user' => count(array_filter($users, function($u) { return $u['role'] == 'user'; })),
                        'active' => count(array_filter($users, function($u) { return $u['status'] == 'active'; })),
                        'inactive' => count(array_filter($users, function($u) { return $u['status'] == 'inactive'; }))
                    ];
                    ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div style="background: #3c8dbc; color: white; padding: 15px; border-radius: 5px; text-align: center;">
                            <h4 style="margin: 0; font-size: 1.5em;"><?php echo $stats['total']; ?></h4>
                            <p style="margin: 5px 0 0 0; font-size: 14px;">Total</p>
                        </div>
                        <div style="background: #dd4b39; color: white; padding: 15px; border-radius: 5px; text-align: center;">
                            <h4 style="margin: 0; font-size: 1.5em;"><?php echo $stats['admin']; ?></h4>
                            <p style="margin: 5px 0 0 0; font-size: 14px;">Admins</p>
                        </div>
                        <div style="background: #f39c12; color: white; padding: 15px; border-radius: 5px; text-align: center;">
                            <h4 style="margin: 0; font-size: 1.5em;"><?php echo $stats['editor']; ?></h4>
                            <p style="margin: 5px 0 0 0; font-size: 14px;">Editores</p>
                        </div>
                        <div style="background: #3c8dbc; color: white; padding: 15px; border-radius: 5px; text-align: center;">
                            <h4 style="margin: 0; font-size: 1.5em;"><?php echo $stats['user']; ?></h4>
                            <p style="margin: 5px 0 0 0; font-size: 14px;">Usuarios</p>
                        </div>
                        <div style="background: #00a65a; color: white; padding: 15px; border-radius: 5px; text-align: center;">
                            <h4 style="margin: 0; font-size: 1.5em;"><?php echo $stats['active']; ?></h4>
                            <p style="margin: 5px 0 0 0; font-size: 14px;">Activos</p>
                        </div>
                        <div style="background: #6c757d; color: white; padding: 15px; border-radius: 5px; text-align: center;">
                            <h4 style="margin: 0; font-size: 1.5em;"><?php echo $stats['inactive']; ?></h4>
                            <p style="margin: 5px 0 0 0; font-size: 14px;">Inactivos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/adminlte-custom.js"></script>
</body>
</html>
