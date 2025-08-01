<?php
/**
 * Editar/Crear Usuario
 * Sistema de Login Dinámico con Roles
 */

// Incluir configuración
require_once 'config.php';
require_once 'inc/functions.php';

// Verificar que el usuario esté logueado
requireLogin();

// Obtener información del usuario actual
$currentUser = getCurrentUser($pdo);

// Determinar si es edición o creación
$isEdit = isset($_GET['id']) && is_numeric($_GET['id']);
$userId = $isEdit ? (int)$_GET['id'] : null;

// Verificar permisos
if ($isEdit) {
    // Si no es admin y no es su propio perfil, denegar acceso
    if (!checkUserRole('admin') && $userId != $currentUser['id']) {
        redirect('dashboard.php?message=access_denied');
    }
} else {
    // Solo admins pueden crear usuarios
    requireRole('admin');
}

// Obtener datos del usuario a editar
$editUser = null;
if ($isEdit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $editUser = $stmt->fetch();
        
        if (!$editUser) {
            redirect('user_list.php?message=user_not_found');
        }
    } catch (PDOException $e) {
        redirect('user_list.php?message=error');
    }
}

// Variables para el formulario
$errors = [];
$success = '';
$formData = [
    'username' => $editUser['username'] ?? '',
    'email' => $editUser['email'] ?? '',
    'full_name' => $editUser['full_name'] ?? '',
    'role' => $editUser['role'] ?? 'user',
    'status' => $editUser['status'] ?? 'active'
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $formData['username'] = clean_input($_POST['username'] ?? '');
    $formData['email'] = clean_input($_POST['email'] ?? '');
    $formData['full_name'] = clean_input($_POST['full_name'] ?? '');
    $formData['role'] = clean_input($_POST['role'] ?? 'user');
    $formData['status'] = clean_input($_POST['status'] ?? 'active');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validaciones
    if (empty($formData['username'])) {
        $errors[] = 'El nombre de usuario es requerido.';
    } elseif (strlen($formData['username']) < 3) {
        $errors[] = 'El nombre de usuario debe tener al menos 3 caracteres.';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'El email es requerido.';
    } elseif (!isValidEmail($formData['email'])) {
        $errors[] = 'El email no es válido.';
    }
    
    if (empty($formData['full_name'])) {
        $errors[] = 'El nombre completo es requerido.';
    }
    
    if (!in_array($formData['role'], ['admin', 'editor', 'user'])) {
        $errors[] = 'El rol seleccionado no es válido.';
    }
    
    if (!in_array($formData['status'], ['active', 'inactive'])) {
        $errors[] = 'El estado seleccionado no es válido.';
    }
    
    // Validar contraseña solo si es creación o si se proporcionó
    if (!$isEdit || !empty($password)) {
        if (empty($password)) {
            $errors[] = 'La contraseña es requerida.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Las contraseñas no coinciden.';
        }
    }
    
    // Verificar que el usuario no se quite permisos de admin a sí mismo
    if ($isEdit && $userId == $currentUser['id'] && $currentUser['role'] == 'admin' && $formData['role'] != 'admin') {
        $errors[] = 'No puede cambiar su propio rol de administrador.';
    }
    
    // Verificar que el usuario no se desactive a sí mismo
    if ($isEdit && $userId == $currentUser['id'] && $formData['status'] != 'active') {
        $errors[] = 'No puede desactivar su propia cuenta.';
    }
    
    // Verificar duplicados
    if (empty($errors)) {
        try {
            // Verificar username duplicado
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?" . ($isEdit ? " AND id != ?" : ""));
            $params = [$formData['username']];
            if ($isEdit) $params[] = $userId;
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $errors[] = 'El nombre de usuario ya está en uso.';
            }
            
            // Verificar email duplicado
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?" . ($isEdit ? " AND id != ?" : ""));
            $params = [$formData['email']];
            if ($isEdit) $params[] = $userId;
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $errors[] = 'El email ya está en uso.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Error al verificar duplicados.';
        }
    }
    
    // Si no hay errores, procesar
    if (empty($errors)) {
        try {
            if ($isEdit) {
                // Actualizar usuario
                if (!empty($password)) {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, status = ?, password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([
                        $formData['username'],
                        $formData['email'],
                        $formData['full_name'],
                        $formData['role'],
                        $formData['status'],
                        hashPassword($password),
                        $userId
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([
                        $formData['username'],
                        $formData['email'],
                        $formData['full_name'],
                        $formData['role'],
                        $formData['status'],
                        $userId
                    ]);
                }
                
                // Registrar actividad
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $currentUser['id'],
                    'update_user',
                    "Usuario actualizado: {$formData['username']}",
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                // Si el usuario editó su propio perfil, actualizar sesión
                if ($userId == $currentUser['id']) {
                    $_SESSION['username'] = $formData['username'];
                    $_SESSION['user_role'] = $formData['role'];
                    $_SESSION['full_name'] = $formData['full_name'];
                }
                
                $success = 'Usuario actualizado exitosamente.';
                
                // Recargar datos del usuario
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $editUser = $stmt->fetch();
                $formData = [
                    'username' => $editUser['username'],
                    'email' => $editUser['email'],
                    'full_name' => $editUser['full_name'],
                    'role' => $editUser['role'],
                    'status' => $editUser['status']
                ];
                
            } else {
                // Crear nuevo usuario
                $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, role, status, password) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $formData['username'],
                    $formData['email'],
                    $formData['full_name'],
                    $formData['role'],
                    $formData['status'],
                    hashPassword($password)
                ]);
                
                // Registrar actividad
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $currentUser['id'],
                    'create_user',
                    "Usuario creado: {$formData['username']}",
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                redirect('user_list.php?message=user_created');
            }
            
        } catch (PDOException $e) {
            $errors[] = 'Error al ' . ($isEdit ? 'actualizar' : 'crear') . ' el usuario.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Editar Usuario' : 'Crear Usuario'; ?> - <?php echo SITE_NAME; ?></title>
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
                <?php if (checkUserRole('admin')): ?>
                <li>
                    <a href="user_list.php">
                        Gestión de Usuarios
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="user_edit.php?id=<?php echo $currentUser['id']; ?>" <?php echo ($isEdit && $userId == $currentUser['id']) ? 'class="active"' : ''; ?>>
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
            <!-- Breadcrumb -->
            <div style="margin-bottom: 20px;">
                <a href="dashboard.php" style="color: #3c8dbc; text-decoration: none;">Dashboard</a>
                <?php if (checkUserRole('admin') && !($isEdit && $userId == $currentUser['id'])): ?>
                    > <a href="user_list.php" style="color: #3c8dbc; text-decoration: none;">Gestión de Usuarios</a>
                <?php endif; ?>
                > <span><?php echo $isEdit ? 'Editar Usuario' : 'Crear Usuario'; ?></span>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">
                        <?php echo $isEdit ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?>
                        <?php if ($isEdit && $userId == $currentUser['id']): ?>
                            <small style="color: #666;">(Mi Perfil)</small>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="box-body">
                    <form method="POST" action="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                            <!-- Columna izquierda -->
                            <div>
                                <div class="form-group">
                                    <label for="username" class="form-label">Nombre de Usuario *</label>
                                    <input type="text" 
                                           id="username" 
                                           name="username" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($formData['username']); ?>"
                                           required>
                                    <small style="color: #666;">Mínimo 3 caracteres, solo letras, números y guiones bajos.</small>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($formData['email']); ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="full_name" class="form-label">Nombre Completo *</label>
                                    <input type="text" 
                                           id="full_name" 
                                           name="full_name" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($formData['full_name']); ?>"
                                           required>
                                </div>
                            </div>

                            <!-- Columna derecha -->
                            <div>
                                <?php if (checkUserRole('admin') && (!$isEdit || $userId != $currentUser['id'])): ?>
                                <div class="form-group">
                                    <label for="role" class="form-label">Rol *</label>
                                    <select id="role" name="role" class="form-control form-select" required>
                                        <option value="user" <?php echo $formData['role'] == 'user' ? 'selected' : ''; ?>>Usuario</option>
                                        <option value="editor" <?php echo $formData['role'] == 'editor' ? 'selected' : ''; ?>>Editor</option>
                                        <option value="admin" <?php echo $formData['role'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="status" class="form-label">Estado *</label>
                                    <select id="status" name="status" class="form-control form-select" required>
                                        <option value="active" <?php echo $formData['status'] == 'active' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactive" <?php echo $formData['status'] == 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                                <?php else: ?>
                                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($formData['role']); ?>">
                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($formData['status']); ?>">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Rol Actual</label>
                                        <div class="form-control" style="background-color: #f8f9fa;">
                                            <span class="badge badge-<?php echo $formData['role'] == 'admin' ? 'danger' : ($formData['role'] == 'editor' ? 'warning' : 'info'); ?>">
                                                <?php echo ucfirst($formData['role']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Estado Actual</label>
                                        <div class="form-control" style="background-color: #f8f9fa;">
                                            <span class="badge badge-<?php echo $formData['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($formData['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Avatar preview -->
                                <div class="form-group">
                                    <label class="form-label">Avatar</label>
                                    <div style="text-align: center;">
                                        <img src="https://placehold.co/100x100?text=<?php echo substr($formData['full_name'] ?: 'U', 0, 1); ?>" 
                                             alt="Avatar de <?php echo htmlspecialchars($formData['full_name'] ?: 'Usuario'); ?>" 
                                             style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid #3c8dbc;"
                                             onerror="this.src='https://placehold.co/100x100?text=U';">
                                        <br><small style="color: #666;">Avatar generado automáticamente</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de contraseña -->
                        <div style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px;">
                            <h4 style="margin-bottom: 20px; color: #444;">
                                <?php echo $isEdit ? 'Cambiar Contraseña (opcional)' : 'Contraseña *'; ?>
                            </h4>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label for="password" class="form-label">
                                        <?php echo $isEdit ? 'Nueva Contraseña' : 'Contraseña *'; ?>
                                    </label>
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           class="form-control"
                                           <?php echo $isEdit ? '' : 'required'; ?>>
                                    <small style="color: #666;">Mínimo 6 caracteres.</small>
                                    <div class="password-strength"></div>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">
                                        Confirmar Contraseña <?php echo $isEdit ? '' : '*'; ?>
                                    </label>
                                    <input type="password" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           class="form-control"
                                           <?php echo $isEdit ? '' : 'required'; ?>>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; text-align: right;">
                            <?php if (checkUserRole('admin') && !($isEdit && $userId == $currentUser['id'])): ?>
                                <a href="user_list.php" class="btn btn-secondary">Cancelar</a>
                            <?php else: ?>
                                <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary">
                                <?php echo $isEdit ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información adicional para edición -->
            <?php if ($isEdit && $editUser): ?>
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Información Adicional</h3>
                </div>
                <div class="box-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <strong>ID de Usuario:</strong> <?php echo $editUser['id']; ?><br>
                            <strong>Fecha de Registro:</strong> <?php echo formatDate($editUser['created_at']); ?><br>
                            <strong>Última Actualización:</strong> <?php echo formatDate($editUser['updated_at']); ?>
                        </div>
                        <div>
                            <?php if (checkUserRole('admin')): ?>
                                <strong>Acciones Administrativas:</strong><br>
                                <?php if ($userId != $currentUser['id']): ?>
                                    <a href="user_list.php?delete=<?php echo $userId; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('¿Está seguro de que desea eliminar este usuario?');">
                                        Eliminar Usuario
                                    </a>
                                <?php else: ?>
                                    <small style="color: #666;">No puede eliminar su propia cuenta.</small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/adminlte-custom.js"></script>
</body>
</html>
