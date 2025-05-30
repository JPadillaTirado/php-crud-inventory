
<?php
// ==============================================
// ARCHIVO DE CONFIGURACIÓN DE EJEMPLO
// ==============================================
// Copia este archivo como config.php y actualiza con tus datos reales
// IMPORTANTE: config.php no debe subirse a Git por seguridad

// Evitar mostrar errores en producción (cambia a 0 en producción)
ini_set('display_errors', 1);

// Configuración de la base de datos
// REEMPLAZA estos valores con los reales en tu config.php local
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario_bd');          // Cambia por tu usuario real
define('DB_PASS', 'tu_contraseña_bd');       // Cambia por tu contraseña real
define('DB_NAME', 'tu_nombre_bd');           // Cambia por tu base de datos real
define('DB_CHARSET', 'utf8');

// Configuración de rutas del servidor
// ACTUALIZA la URL base según tu entorno
define('BASE_URL', 'http://localhost/tu-proyecto/');  // Cambia por tu URL
define('SERVIDOR', dirname(__DIR__) . '/');           // Ruta absoluta del proyecto

// Configuración para subida de archivos
define('UPLOADS_DIR', SERVIDOR . 'uploads/');         // Directorio para subir archivos
define('UPLOADS_URL', BASE_URL . 'uploads/');         // URL para acceder a los archivos

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Inventario');
define('APP_EMAIL', 'admin@ejemplo.com');             // Cambia por tu email
define('ITEMS_POR_PAGINA', 10);                       // Para paginación

// Mensaje para errores generales
define('ERROR_MSG', 'Ha ocurrido un error inesperado');

// Zona horaria
date_default_timezone_set('America/Bogota');          // Ajusta a tu zona horaria
?>