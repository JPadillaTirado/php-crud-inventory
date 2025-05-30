<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conexion.php';

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=Error de conexión a la base de datos');
    exit;
}

// Verificar que el formulario haya sido enviado por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=Método no permitido');
    exit;
}

// Obtener y validar datos del formulario
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$clave = isset($_POST['clave']) ? $_POST['clave'] : '';
$confirmar_clave = isset($_POST['confirmar_clave']) ? $_POST['confirmar_clave'] : '';

// Validaciones básicas
if (empty($nombre)) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=El nombre es obligatorio');
    exit;
}

if (empty($apellido)) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=El apellido es obligatorio');
    exit;
}

if (empty($usuario)) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=El nombre de usuario es obligatorio');
    exit;
}

// Validar longitud del nombre de usuario
if (strlen($usuario) < 4 || strlen($usuario) > 20) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=El nombre de usuario debe tener entre 4 y 20 caracteres');
    exit;
}

// Validar formato de email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=Correo electrónico inválido');
    exit;
}

// Validar contraseña
if (empty($clave)) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=La contraseña es obligatoria');
    exit;
}

if (strlen($clave) < 6) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=La contraseña debe tener al menos 6 caracteres');
    exit;
}

// Validar que la contraseña contenga al menos una letra y un número
if (!preg_match('/[A-Za-z]/', $clave) || !preg_match('/[0-9]/', $clave)) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=La contraseña debe contener al menos una letra y un número');
    exit;
}

// Verificar que las contraseñas coincidan
if ($clave !== $confirmar_clave) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=Las contraseñas no coinciden');
    exit;
}

// Verificar que el nombre de usuario no esté duplicado
$sql_check_usuario = "SELECT usuario_id FROM usuario WHERE usuario_usuario = ?";
$stmt_check_usuario = $conexion->prepare($sql_check_usuario);
$stmt_check_usuario->bind_param("s", $usuario);
$stmt_check_usuario->execute();
$resultado_check_usuario = $stmt_check_usuario->get_result();

if ($resultado_check_usuario->num_rows > 0) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=El nombre de usuario ya está en uso');
    exit;
}

// Verificar que el correo electrónico no esté duplicado
$sql_check_email = "SELECT usuario_id FROM usuario WHERE usuario_email = ?";
$stmt_check_email = $conexion->prepare($sql_check_email);
$stmt_check_email->bind_param("s", $email);
$stmt_check_email->execute();
$resultado_check_email = $stmt_check_email->get_result();

if ($resultado_check_email->num_rows > 0) {
    header('Location: ../views/usuarios/usuario_nuevo.php?error=El correo electrónico ya está registrado');
    exit;
}

// Generar hash de la contraseña
$clave_hash = password_hash($clave, PASSWORD_DEFAULT);

// Insertar usuario en la base de datos
$sql = "INSERT INTO usuario (usuario_nombre, usuario_apellido, usuario_usuario, usuario_email, usuario_clave) 
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssss", $nombre, $apellido, $usuario, $email, $clave_hash);

if ($stmt->execute()) {
    // Redirigir al listado con mensaje de éxito
    header('Location: ../views/usuarios/usuario_listar.php?mensaje=Usuario registrado correctamente&tipo=exito');
    exit;
} else {
    // Redirigir al formulario con mensaje de error
    header('Location: ../views/usuarios/usuario_nuevo.php?error=Error al registrar el usuario: ' . $conexion->error);
    exit;
}