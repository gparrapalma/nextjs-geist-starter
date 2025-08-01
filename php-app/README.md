# Sistema de Login Dinámico con Roles - PHP

Un sistema completo de autenticación y gestión de usuarios desarrollado en PHP con diseño inspirado en AdminLTE, roles de usuario y base de datos MySQL.

## 🚀 Características

- **Sistema de Login Seguro**: Autenticación con contraseñas hasheadas
- **Gestión de Roles**: Admin, Editor y Usuario con permisos diferenciados
- **Panel de Administración**: Interfaz moderna inspirada en AdminLTE
- **Gestión de Usuarios**: CRUD completo para administradores
- **Responsive Design**: Compatible con dispositivos móviles
- **Logs de Actividad**: Registro de acciones del sistema
- **Validación de Formularios**: Validación tanto cliente como servidor
- **Seguridad**: Protección contra SQL Injection y XSS

## 📋 Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.2 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP requeridas:
  - PDO
  - PDO_MySQL
  - mbstring
  - session

## 🛠️ Instalación

### 1. Clonar/Descargar el proyecto
```bash
# Si tienes el proyecto en un repositorio
git clone [url-del-repositorio]

# O simplemente copia la carpeta php-app a tu servidor web
```

### 2. Configurar la base de datos

1. Crear la base de datos MySQL:
```sql
CREATE DATABASE login_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importar el esquema de la base de datos:
```bash
mysql -u tu_usuario -p login_app < db.sql
```

O ejecutar el contenido del archivo `db.sql` en tu cliente MySQL preferido.

### 3. Configurar la conexión a la base de datos

Editar el archivo `config.php` y actualizar las credenciales:

```php
define('DB_HOST', 'localhost');        // Host de la base de datos
define('DB_NAME', 'login_app');        // Nombre de la base de datos
define('DB_USER', 'tu_usuario');       // Usuario de MySQL
define('DB_PASS', 'tu_contraseña');    // Contraseña de MySQL
```

### 4. Configurar permisos (Linux/macOS)
```bash
chmod 755 php-app/
chmod 644 php-app/*.php
chmod 644 php-app/assets/css/*.css
chmod 644 php-app/assets/js/*.js
```

### 5. Configurar el servidor web

#### Apache (.htaccess)
Crear un archivo `.htaccess` en la carpeta `php-app/`:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Seguridad adicional
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>

<Files "db.sql">
    Order allow,deny
    Deny from all
</Files>
```

#### Nginx
```nginx
location /php-app/ {
    try_files $uri $uri/ /php-app/index.php?$query_string;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Proteger archivos sensibles
    location ~ ^/php-app/(config\.php|db\.sql)$ {
        deny all;
    }
}
```

## 👥 Usuarios por Defecto

El sistema incluye usuarios de prueba con las siguientes credenciales:

| Usuario | Contraseña | Rol | Descripción |
|---------|------------|-----|-------------|
| `admin` | `admin123` | Administrador | Acceso completo al sistema |
| `editor` | `editor123` | Editor | Acceso limitado |
| `usuario` | `user123` | Usuario | Acceso básico |

**⚠️ IMPORTANTE**: Cambiar estas contraseñas en producción.

## 🎯 Uso del Sistema

### Acceso al Sistema
1. Navegar a `http://tu-servidor/php-app/`
2. Usar las credenciales de prueba o crear nuevos usuarios
3. El sistema redirigirá automáticamente según el estado de la sesión

### Roles y Permisos

#### Administrador
- Acceso completo al dashboard
- Gestión de usuarios (crear, editar, eliminar)
- Ver logs de actividad
- Editar su propio perfil

#### Editor
- Acceso al dashboard
- Editar su propio perfil
- Ver información básica del sistema

#### Usuario
- Acceso al dashboard
- Editar su propio perfil
- Ver información básica

### Funcionalidades Principales

#### Dashboard
- Estadísticas del sistema
- Información del usuario actual
- Actividad reciente (solo admins)
- Accesos rápidos

#### Gestión de Usuarios (Solo Admins)
- Lista de usuarios con filtros
- Crear nuevos usuarios
- Editar usuarios existentes
- Eliminar usuarios
- Cambiar roles y estados

#### Perfil de Usuario
- Editar información personal
- Cambiar contraseña
- Ver información de la cuenta

## 🔧 Estructura del Proyecto

```
php-app/
├── assets/
│   ├── css/
│   │   └── adminlte-custom.css    # Estilos personalizados
│   ├── js/
│   │   └── adminlte-custom.js     # JavaScript personalizado
│   └── images/                    # Imágenes del sistema
├── inc/
│   └── functions.php              # Funciones comunes
├── config.php                     # Configuración de la base de datos
├── index.php                      # Página principal (redirección)
├── login.php                      # Página de login
├── logout.php                     # Cerrar sesión
├── dashboard.php                  # Panel principal
├── user_list.php                  # Lista de usuarios (admin)
├── user_edit.php                  # Crear/editar usuarios
├── db.sql                         # Esquema de la base de datos
└── README.md                      # Este archivo
```

## 🔒 Seguridad

### Medidas Implementadas
- Contraseñas hasheadas con `password_hash()`
- Consultas preparadas (PDO) para prevenir SQL Injection
- Validación y sanitización de datos de entrada
- Protección XSS con `htmlspecialchars()`
- Control de acceso basado en roles
- Logs de actividad del sistema
- Validación de sesiones

### Recomendaciones Adicionales
1. Usar HTTPS en producción
2. Configurar límites de intentos de login
3. Implementar CSRF tokens
4. Configurar headers de seguridad
5. Mantener PHP y MySQL actualizados
6. Realizar backups regulares

## 🎨 Personalización

### Cambiar Colores y Estilos
Editar el archivo `assets/css/adminlte-custom.css` para personalizar:
- Colores del tema
- Tipografías
- Espaciados
- Responsive breakpoints

### Agregar Nuevos Roles
1. Modificar la tabla `users` en la base de datos
2. Actualizar las funciones en `inc/functions.php`
3. Ajustar las validaciones en los formularios

### Personalizar el Dashboard
Editar `dashboard.php` para agregar:
- Nuevas estadísticas
- Widgets personalizados
- Gráficos y reportes

## 🐛 Solución de Problemas

### Error de Conexión a la Base de Datos
- Verificar credenciales en `config.php`
- Confirmar que MySQL esté ejecutándose
- Verificar que la base de datos existe

### Problemas de Permisos
- Verificar permisos de archivos y carpetas
- Confirmar configuración del servidor web
- Revisar logs de error del servidor

### Sesiones no Funcionan
- Verificar configuración de PHP sessions
- Confirmar que las cookies estén habilitadas
- Revisar la configuración de `session.save_path`

## 📝 Logs y Debugging

### Logs de Actividad
El sistema registra automáticamente:
- Inicios y cierres de sesión
- Creación, edición y eliminación de usuarios
- Acciones administrativas

### Debugging
Para habilitar el modo debug, agregar al inicio de `config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🤝 Contribuciones

Para contribuir al proyecto:
1. Fork del repositorio
2. Crear una rama para tu feature
3. Commit de los cambios
4. Push a la rama
5. Crear un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o reportar bugs:
- Crear un issue en el repositorio
- Contactar al equipo de desarrollo
- Revisar la documentación y FAQ

---

**Desarrollado con ❤️ usando PHP, MySQL y AdminLTE**
