<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: ../views/proveedores/proveedor_nuevo.php?error=Error de conexión a la base de datos');
    exit;
}

// Verificar que el formulario haya sido enviado por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/proveedores/proveedor_nuevo.php?error=Método no permitido');
    exit;
}

// Obtener y validar datos del formulario
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$rut_nit = isset($_POST['rut_nit']) ? trim($_POST['rut_nit']) : '';
$contacto = isset($_POST['contacto']) ? trim($_POST['contacto']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
$ciudad = isset($_POST['ciudad']) ? trim($_POST['ciudad']) : '';
$estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;

// Validaciones básicas
if (empty($nombre)) {
    header('Location: ../views/proveedores/proveedor_nuevo.php?error=El nombre de la empresa es obligatorio');
    exit;
}

if (empty($rut_nit)) {
    header('Location: ../views/proveedores/proveedor_nuevo.php?error=El RUT/NIT es obligatorio');
    exit;
}

// Validar formato de email si se proporciona
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../views/proveedores/proveedor_nuevo.php?error=El formato del email no es válido');
    exit;
}

// Verificar que el RUT/NIT no esté duplicado
$sql_check = "SELECT proveedor_id FROM proveedor WHERE proveedor_rut_nit = ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("s", $rut_nit);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();

if ($resultado_check->num_rows > 0) {
    header('Location: ../views/proveedores/proveedor_nuevo.php?error=Ya existe un proveedor con ese RUT/NIT');
    exit;
}

// Insertar proveedor en la base de datos
$sql = "INSERT INTO proveedor (
            proveedor_nombre, 
            proveedor_rut_nit, 
            proveedor_telefono, 
            proveedor_email, 
            proveedor_direccion, 
            proveedor_ciudad, 
            proveedor_contacto, 
            proveedor_estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssssssi", $nombre, $rut_nit, $telefono, $email, $direccion, $ciudad, $contacto, $estado);

if ($stmt->execute()) {
    // Redirigir al listado con mensaje de éxito
    header('Location: ../views/proveedores/proveedor_listar.php?mensaje=Proveedor agregado correctamente&tipo=exito');
    exit;
} else {
    // Redirigir al formulario con mensaje de error
    header('Location: ../views/proveedores/proveedor_nuevo.php?error=Error al guardar el proveedor: ' . $conexion->error);
    exit;
}