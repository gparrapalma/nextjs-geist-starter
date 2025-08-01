<?php
/**
 * Configuración DEMO - Sin base de datos
 * Sistema de Login Dinámico con Roles
 */

// Configuraciones globales
define('SITE_NAME', 'Sistema de Login Dinámico - DEMO');
define('SITE_URL', 'http://localhost:8000');

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Usuarios demo en memoria (simulando base de datos)
$demo_users = [
    1 => [
        'id' => 1,
        'username' => 'admin',
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // admin123
        'email' => 'admin@sistema.com',
        'full_name' => 'Administrador del Sistema',
        'role' => 'admin',
        'status' => 'active',
        'created_at' => '2025-01-01 10:00:00',
        'updated_at' => '2025-01-01 10:00:00'
    ],
    2 => [
        'id' => 2,
        'username' => 'editor',
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // editor123
        'email' => 'editor@sistema.com',
        'full_name' => 'Editor del Sistema',
        'role' => 'editor',
        'status' => 'active',
        'created_at' => '2025-01-01 10:00:00',
        'updated_at' => '2025-01-01 10:00:00'
    ],
    3 => [
        'id' => 3,
        'username' => 'usuario',
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // user123
        'email' => 'usuario@sistema.com',
        'full_name' => 'Usuario Regular',
        'role' => 'user',
        'status' => 'active',
        'created_at' => '2025-01-01 10:00:00',
        'updated_at' => '2025-01-01 10:00:00'
    ]
];

// Logs de actividad demo
$demo_logs = [
    [
        'id' => 1,
        'user_id' => 1,
        'action' => 'login',
        'description' => 'Usuario inició sesión',
        'ip_address' => '127.0.0.1',
        'created_at' => '2025-01-01 10:30:00',
        'username' => 'admin',
        'full_name' => 'Administrador del Sistema'
    ],
    [
        'id' => 2,
        'user_id' => 2,
        'action' => 'login',
        'description' => 'Usuario inició sesión',
        'ip_address' => '127.0.0.1',
        'created_at' => '2025-01-01 11:00:00',
        'username' => 'editor',
        'full_name' => 'Editor del Sistema'
    ]
];

// Clase PDO simulada para compatibilidad
class DemoPDO {
    private $users;
    private $logs;
    
    public function __construct() {
        global $demo_users, $demo_logs;
        $this->users = $demo_users;
        $this->logs = $demo_logs;
    }
    
    public function prepare($sql) {
        return new DemoStatement($sql, $this->users, $this->logs);
    }
    
    public function query($sql) {
        if (strpos($sql, 'COUNT(*) as total FROM users') !== false) {
            $stmt = new DemoStatement($sql, $this->users, $this->logs);
            $stmt->execute();
            return $stmt;
        }
        return new DemoStatement($sql, $this->users, $this->logs);
    }
    
    public function exec($sql) {
        return true;
    }
    
    public function setAttribute($attr, $value) {
        return true;
    }
}

class DemoStatement {
    private $sql;
    private $users;
    private $logs;
    private $result;
    
    public function __construct($sql, $users, $logs) {
        $this->sql = $sql;
        $this->users = $users;
        $this->logs = $logs;
    }
    
    public function execute($params = []) {
        if (strpos($this->sql, 'SELECT * FROM users WHERE username = ?') !== false) {
            foreach ($this->users as $user) {
                if ($user['username'] === $params[0] && $user['status'] === 'active') {
                    $this->result = [$user];
                    return true;
                }
            }
            $this->result = [];
        } elseif (strpos($this->sql, 'SELECT * FROM users WHERE id = ?') !== false) {
            if (isset($this->users[$params[0]])) {
                $this->result = [$this->users[$params[0]]];
            } else {
                $this->result = [];
            }
        } elseif (strpos($this->sql, 'COUNT(*) as total FROM users') !== false) {
            $this->result = [['total' => count($this->users)]];
        } elseif (strpos($this->sql, 'SELECT role, COUNT(*) as count FROM users') !== false) {
            $roles = [];
            foreach ($this->users as $user) {
                if (!isset($roles[$user['role']])) {
                    $roles[$user['role']] = 0;
                }
                $roles[$user['role']]++;
            }
            $this->result = [];
            foreach ($roles as $role => $count) {
                $this->result[] = ['role' => $role, 'count' => $count];
            }
        } elseif (strpos($this->sql, 'activity_logs') !== false) {
            $this->result = $this->logs;
        } elseif (strpos($this->sql, 'INSERT INTO activity_logs') !== false) {
            return true;
        } else {
            $this->result = array_values($this->users);
        }
        return true;
    }
    
    public function fetch() {
        if (!empty($this->result)) {
            return array_shift($this->result);
        }
        return false;
    }
    
    public function fetchAll() {
        return $this->result ?: [];
    }
}

// Crear instancia PDO simulada
$pdo = new DemoPDO();

// Zona horaria
date_default_timezone_set('America/Santiago');
?>
