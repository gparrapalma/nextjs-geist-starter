<?php
/**
 * Script de Instalación Automática
 * Sistema de Login Dinámico con Roles
 * 
 * IMPORTANTE: Eliminar este archivo después de la instalación
 */

// Verificar si ya está instalado
if (file_exists('config.php')) {
    $config_content = file_get_contents('config.php');
    if (strpos($config_content, 'DB_USER') !== false && 
        strpos($config_content, 'root') === false && 
        strpos($config_content, 'tu_usuario') === false) {
        die('<h1>Sistema ya instalado</h1><p>El sistema ya ha sido configurado. Si necesita reinstalar, elimine el archivo config.php primero.</p><p><a href="index.php">Ir al sistema</a></p>');
    }
}

$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

// Función para verificar requisitos
function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'MBString Extension' => extension_loaded('mbstring'),
        'Session Support' => function_exists('session_start'),
        'Hash Support' => function_exists('password_hash'),
        'Config File Writable' => is_writable('.') || is_writable('config.php')
    ];
    
    return $requirements;
}

// Función para probar conexión a la base de datos
function testDatabaseConnection($host, $dbname, $username, $password) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

// Función para crear las tablas
function createTables($pdo) {
    $sql = file_get_contents('db.sql');
    
    // Dividir el SQL en statements individuales
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE)/i', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                throw new Exception("Error ejecutando SQL: " . $e->getMessage());
            }
        }
    }
    
    return true;
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 2) {
        // Validar datos de conexión
        $db_host = trim($_POST['db_host'] ?? '');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = $_POST['db_pass'] ?? '';
        $site_name = trim($_POST['site_name'] ?? 'Sistema de Login Dinámico');
        
        if (empty($db_host) || empty($db_name) || empty($db_user)) {
            $errors[] = 'Todos los campos de conexión son requeridos excepto la contraseña.';
        } else {
            // Probar conexión
            $connection_test = testDatabaseConnection($db_host, $db_name, $db_user, $db_pass);
            
            if ($connection_test === true) {
                // Crear archivo de configuración
                $config_content = "<?php\n";
                $config_content .= "/**\n";
                $config_content .= " * Configuración de la base de datos y configuraciones globales\n";
                $config_content .= " * Sistema de Login Dinámico con Roles\n";
                $config_content .= " * Generado automáticamente el " . date('Y-m-d H:i:s') . "\n";
                $config_content .= " */\n\n";
                $config_content .= "// Configuración de la base de datos MySQL\n";
                $config_content .= "define('DB_HOST', '" . addslashes($db_host) . "');\n";
                $config_content .= "define('DB_NAME', '" . addslashes($db_name) . "');\n";
                $config_content .= "define('DB_USER', '" . addslashes($db_user) . "');\n";
                $config_content .= "define('DB_PASS', '" . addslashes($db_pass) . "');\n\n";
                $config_content .= "// Configuraciones globales\n";
                $config_content .= "define('SITE_NAME', '" . addslashes($site_name) . "');\n";
                $config_content .= "define('SITE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['SCRIPT_NAME']));\n\n";
                $config_content .= "// Iniciar sesión si no está iniciada\n";
                $config_content .= "if (session_status() == PHP_SESSION_NONE) {\n";
                $config_content .= "    session_start();\n";
                $config_content .= "}\n\n";
                $config_content .= "// Conexión a la base de datos usando PDO\n";
                $config_content .= "try {\n";
                $config_content .= "    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8\", DB_USER, DB_PASS);\n";
                $config_content .= "    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";
                $config_content .= "    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);\n";
                $config_content .= "} catch (PDOException \$e) {\n";
                $config_content .= "    die(\"Error de conexión a la base de datos: \" . \$e->getMessage());\n";
                $config_content .= "}\n\n";
                $config_content .= "// Zona horaria\n";
                $config_content .= "date_default_timezone_set('America/Santiago');\n";
                $config_content .= "?>";
                
                if (file_put_contents('config.php', $config_content)) {
                    $success[] = 'Archivo de configuración creado exitosamente.';
                    $step = 3;
                } else {
                    $errors[] = 'No se pudo crear el archivo de configuración. Verifique los permisos.';
                }
            } else {
                $errors[] = 'Error de conexión a la base de datos: ' . $connection_test;
            }
        }
    } elseif ($step == 3) {
        // Crear tablas
        try {
            require_once 'config.php';
            createTables($pdo);
            $success[] = 'Base de datos configurada exitosamente.';
            $step = 4;
        } catch (Exception $e) {
            $errors[] = 'Error al crear las tablas: ' . $e->getMessage();
        }
    } elseif ($step == 4) {
        // Configurar usuario administrador
        $admin_user = trim($_POST['admin_user'] ?? '');
        $admin_pass = $_POST['admin_pass'] ?? '';
        $admin_email = trim($_POST['admin_email'] ?? '');
        $admin_name = trim($_POST['admin_name'] ?? '');
        
        if (empty($admin_user) || empty($admin_pass) || empty($admin_email) || empty($admin_name)) {
            $errors[] = 'Todos los campos del administrador son requeridos.';
        } elseif (strlen($admin_pass) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido.';
        } else {
            try {
                require_once 'config.php';
                
                // Eliminar usuarios por defecto
                $pdo->exec("DELETE FROM users");
                
                // Crear usuario administrador
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')");
                $stmt->execute([
                    $admin_user,
                    password_hash($admin_pass, PASSWORD_DEFAULT),
                    $admin_email,
                    $admin_name
                ]);
                
                $success[] = 'Usuario administrador creado exitosamente.';
                $step = 5;
            } catch (Exception $e) {
                $errors[] = 'Error al crear el usuario administrador: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Login Dinámico</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .install-header {
            background: #3c8dbc;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .install-body {
            padding: 30px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: #666;
        }
        
        .step.active {
            background: #3c8dbc;
            color: white;
        }
        
        .step.completed {
            background: #00a65a;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3c8dbc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .btn:hover {
            background: #367fa9;
        }
        
        .btn-success {
            background: #00a65a;
        }
        
        .btn-success:hover {
            background: #008d4c;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-danger {
            background: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        
        .alert-success {
            background: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        
        .requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .requirements-table th,
        .requirements-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .requirements-table th {
            background: #f8f9fa;
        }
        
        .status-ok {
            color: #00a65a;
            font-weight: bold;
        }
        
        .status-error {
            color: #dd4b39;
            font-weight: bold;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mb-3 {
            margin-bottom: 1rem;
        }
        
        small {
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>Instalación del Sistema</h1>
            <p>Sistema de Login Dinámico con Roles</p>
        </div>
        
        <div class="install-body">
            <!-- Indicador de pasos -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? ($step == 1 ? 'active' : 'completed') : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? ($step == 2 ? 'active' : 'completed') : ''; ?>">2</div>
                <div class="step <?php echo $step >= 3 ? ($step == 3 ? 'active' : 'completed') : ''; ?>">3</div>
                <div class="step <?php echo $step >= 4 ? ($step == 4 ? 'active' : 'completed') : ''; ?>">4</div>
                <div class="step <?php echo $step >= 5 ? 'active' : ''; ?>">5</div>
            </div>
            
            <!-- Mostrar errores -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Mostrar éxitos -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($success as $msg): ?>
                            <li><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <!-- Paso 1: Verificar requisitos -->
                <h2>Paso 1: Verificación de Requisitos</h2>
                <p class="mb-3">Verificando que su servidor cumple con los requisitos mínimos:</p>
                
                <table class="requirements-table">
                    <thead>
                        <tr>
                            <th>Requisito</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $requirements = checkRequirements();
                        $all_ok = true;
                        foreach ($requirements as $req => $status):
                            if (!$status) $all_ok = false;
                        ?>
                        <tr>
                            <td><?php echo $req; ?></td>
                            <td class="<?php echo $status ? 'status-ok' : 'status-error'; ?>">
                                <?php echo $status ? '✓ OK' : '✗ Error'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($all_ok): ?>
                    <div class="text-center">
                        <a href="?step=2" class="btn btn-success">Continuar con la Instalación</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> Su servidor no cumple con todos los requisitos. 
                        Por favor, corrija los problemas antes de continuar.
                    </div>
                <?php endif; ?>
                
            <?php elseif ($step == 2): ?>
                <!-- Paso 2: Configuración de base de datos -->
                <h2>Paso 2: Configuración de Base de Datos</h2>
                <p class="mb-3">Configure la conexión a su base de datos MySQL:</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="db_host" class="form-label">Host de la Base de Datos</label>
                        <input type="text" id="db_host" name="db_host" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                        <small>Generalmente 'localhost' para servidores locales</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name" class="form-label">Nombre de la Base de Datos</label>
                        <input type="text" id="db_name" name="db_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'login_app'); ?>" required>
                        <small>La base de datos debe existir previamente</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user" class="form-label">Usuario de MySQL</label>
                        <input type="text" id="db_user" name="db_user" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass" class="form-label">Contraseña de MySQL</label>
                        <input type="password" id="db_pass" name="db_pass" class="form-control">
                        <small>Dejar en blanco si no tiene contraseña</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_name" class="form-label">Nombre del Sistema</label>
                        <input type="text" id="site_name" name="site_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['site_name'] ?? 'Sistema de Login Dinámico'); ?>" required>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">Probar Conexión y Continuar</button>
                    </div>
                </form>
                
            <?php elseif ($step == 3): ?>
                <!-- Paso 3: Crear tablas -->
                <h2>Paso 3: Configuración de Base de Datos</h2>
                <p class="mb-3">Ahora crearemos las tablas necesarias en la base de datos:</p>
                
                <form method="POST">
                    <div class="alert alert-success">
                        <strong>Conexión exitosa!</strong> La conexión a la base de datos se estableció correctamente.
                    </div>
                    
                    <p>Se crearán las siguientes tablas:</p>
                    <ul>
                        <li><strong>users</strong> - Usuarios del sistema</li>
                        <li><strong>user_sessions</strong> - Sesiones de usuario</li>
                        <li><strong>activity_logs</strong> - Logs de actividad</li>
                    </ul>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">Crear Tablas</button>
                    </div>
                </form>
                
            <?php elseif ($step == 4): ?>
                <!-- Paso 4: Crear usuario administrador -->
                <h2>Paso 4: Usuario Administrador</h2>
                <p class="mb-3">Cree la cuenta del administrador principal:</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="admin_user" class="form-label">Nombre de Usuario</label>
                        <input type="text" id="admin_user" name="admin_user" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['admin_user'] ?? 'admin'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_pass" class="form-label">Contraseña</label>
                        <input type="password" id="admin_pass" name="admin_pass" class="form-control" required>
                        <small>Mínimo 6 caracteres</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email" class="form-label">Email</label>
                        <input type="email" id="admin_email" name="admin_email" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['admin_email'] ?? 'admin@sistema.com'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_name" class="form-label">Nombre Completo</label>
                        <input type="text" id="admin_name" name="admin_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['admin_name'] ?? 'Administrador del Sistema'); ?>" required>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">Crear Administrador</button>
                    </div>
                </form>
                
            <?php elseif ($step == 5): ?>
                <!-- Paso 5: Instalación completada -->
                <h2>¡Instalación Completada!</h2>
                
                <div class="alert alert-success">
                    <strong>¡Felicitaciones!</strong> El sistema se ha instalado correctamente.
                </div>
                
                <h3>Próximos pasos:</h3>
                <ol>
                    <li><strong>Elimine este archivo:</strong> Por seguridad, elimine el archivo <code>install.php</code></li>
                    <li><strong>Configure permisos:</strong> Asegúrese de que los archivos tengan los permisos correctos</li>
                    <li><strong>Acceda al sistema:</strong> Use las credenciales del administrador que creó</li>
                </ol>
                
                <h3>Información importante:</h3>
                <ul>
                    <li>Archivo de configuración creado: <code>config.php</code></li>
                    <li>Base de datos configurada con todas las tablas</li>
                    <li>Usuario administrador creado</li>
                    <li>Sistema listo para usar</li>
                </ul>
                
                <div class="text-center" style="margin-top: 30px;">
                    <a href="index.php" class="btn btn-success">Acceder al Sistema</a>
                    <a href="README.md" class="btn" target="_blank">Ver Documentación</a>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <small>
                        <strong>Nota de Seguridad:</strong> 
                        Recuerde eliminar el archivo <code>install.php</code> después de completar la instalación 
                        para evitar reinstalaciones no autorizadas.
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
