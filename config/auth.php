<?php
// ==============================================
// ARCHIVO DE VERIFICACIÓN DE AUTENTICACIÓN
// ==============================================
// Incluir este archivo al inicio de cualquier página que requiera autenticación

session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    // Si no está logueado, redirigir al login
    header('Location: ' . (strpos($_SERVER['REQUEST_URI'], 'views/') !== false ? '../../login.php' : 'login.php'));
    exit();
}

// Función para obtener información del usuario logueado
function getUsuarioLogueado() {
    return [
        'id' => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'apellido' => $_SESSION['usuario_apellido'],
        'usuario' => $_SESSION['usuario_usuario'],
        'email' => $_SESSION['usuario_email']
    ];
}

// Función para obtener el nombre completo del usuario
function getNombreCompleto() {
    return $_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellido'];
}

// Función para verificar si el usuario tiene permisos (futura implementación)
function tienePermisos($permiso) {
    // Por ahora, todos los usuarios logueados tienen todos los permisos
    // En el futuro, aquí se puede implementar un sistema de roles
    return true;
}
?> 