<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: ../views/usuarios/usuario_listar.php?mensaje=Error de conexión a la base de datos&tipo=error');
    exit;
}

// Verificar que el formulario haya sido enviado por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/usuarios/usuario_listar.php?mensaje=Método no permitido&tipo=error');
    exit;
}

// Obtener y validar el ID del usuario
if (!isset($_POST['usuario_id']) || !is_numeric($_POST['usuario_id'])) {
    header('Location: ../views/usuarios/usuario_listar.php?mensaje=ID de usuario no válido&tipo=error');
    exit;
}

$usuario_id = intval($_POST['usuario_id']);

// Obtener y validar datos del formulario
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$clave = isset($_POST['clave']) ? $_POST['clave'] : '';
$confirmar_clave = isset($_POST['confirmar_clave']) ? $_POST['confirmar_clave'] : '';

// Validaciones básicas
if (empty($nombre)) {
    header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=El nombre es obligatorio');
    exit;
}

if (empty($apellido)) {
    header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=El apellido es obligatorio');
    exit;
}

if (empty($usuario)) {
    header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=El nombre de usuario es obligatorio');
    exit;
}

// Validar longitud del nombre de usuario
if (strlen($usuario) < 4 || strlen($usuario) > 20) {
    header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=El nombre de usuario debe tener entre 4 y 20 caracteres');
    exit;
}

// Validar formato de email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=Correo electrónico inválido');
    exit;
}

// Verificar que el nombre de usuario no esté duplicado (excluyendo el usuario actual)
$sql_check_usuario = "SELECT usuario_id FROM usuario WHERE usuario_usuario = ? AND usuario_id != ?";
$stmt_check_usuario = $conexion->prepare($sql_check_usuario);
$stmt_check_usuario->bind_param("si", $usuario, $usuario_id);
$stmt_check_usuario->execute();
$resultado_check_usuario = $stmt_check_usuario->get_result();

if ($resultado_check_usuario->num_rows > 0) {
    header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=El nombre de usuario ya está en uso');
    exit;
}

// Verificar que el correo electrónico no esté duplicado (excluyendo el usuario actual)
$sql_check_email = "SELECT usuario_id FROM usuario WHERE usuario_email = ? AND usuario_id != ?";
$stmt_check_email = $conexion->prepare($sql_check_email);
$stmt_check_email->bind_param("si", $email, $usuario_id);
$stmt_check_email->execute();
$resultado_check_email = $stmt_check_email->get_result();

if ($resultado_check_email->num_rows > 0) {
    header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=El correo electrónico ya está registrado');
    exit;
}

// Si se proporcionó una nueva contraseña, validarla
if (!empty($clave)) {
    if (strlen($clave) < 6) {
        header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=La contraseña debe tener al menos 6 caracteres');
        exit;
    }
    
    // Validar que la contraseña contenga al menos una letra y un número
    if (!preg_match('/[A-Za-z]/', $clave) || !preg_match('/[0-9]/', $clave)) {
        header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=La contraseña debe contener al menos una letra y un número');
        exit;
    }
    
    // Verificar que las contraseñas coincidan
    if ($clave !== $confirmar_clave) {
        header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=Las contraseñas no coinciden');
        exit;
    }
    
    // Generar hash de la nueva contraseña
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
    
    // Actualizar usuario con nueva contraseña
    $sql = "UPDATE usuario SET usuario_nombre = ?, usuario_apellido = ?, usuario_usuario = ?, 
            usuario_email = ?, usuario_clave = ? WHERE usuario_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssi", $nombre, $apellido, $usuario, $email, $clave_hash, $usuario_id);
} else {
    // Actualizar usuario sin cambiar la contraseña
    $sql = "UPDATE usuario SET usuario_nombre = ?, usuario_apellido = ?, usuario_usuario = ?, 
            usuario_email = ? WHERE usuario_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $apellido, $usuario, $email, $usuario_id);
}

if ($stmt->execute()) {
    // Redirigir al listado con mensaje de éxito
    header('Location: ../views/usuarios/usuario_listar.php?mensaje=Usuario actualizado correctamente&tipo=exito');
    exit;
} else {
    // Redirigir al formulario con mensaje de error
    header('Location: ../views/usuarios/usuario_editar.php?id=' . $usuario_id . '&error=Error al actualizar el usuario: ' . $conexion->error);
    exit;
}