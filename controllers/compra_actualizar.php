<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: ../views/compras/compra_listar.php?mensaje=Error de conexión a la base de datos&tipo=error');
    exit;
}

// Verificar que el formulario haya sido enviado por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/compras/compra_listar.php?mensaje=Método no permitido&tipo=error');
    exit;
}

// Obtener y validar el ID de la compra
if (!isset($_POST['compra_id']) || !is_numeric($_POST['compra_id'])) {
    header('Location: ../views/compras/compra_listar.php?mensaje=ID de compra no válido&tipo=error');
    exit;
}

$compra_id = intval($_POST['compra_id']);

// Verificar que la compra existe y está en estado pendiente
$sql_verificar = "SELECT compra_estado FROM compra WHERE compra_id = ?";
$stmt_verificar = $conexion->prepare($sql_verificar);
$stmt_verificar->bind_param("i", $compra_id);
$stmt_verificar->execute();
$resultado_verificar = $stmt_verificar->get_result();

if ($resultado_verificar->num_rows === 0) {
    header('Location: ../views/compras/compra_listar.php?mensaje=La compra no existe&tipo=error');
    exit;
}

$compra_actual = $resultado_verificar->fetch_assoc();
if ($compra_actual['compra_estado'] !== 'pendiente') {
    header("Location: ../views/compras/compra_ver.php?id=$compra_id&mensaje=Solo se pueden editar compras en estado pendiente&tipo=error");
    exit;
}

// Obtener y validar datos básicos del formulario
$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
$proveedor_id = isset($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : 0;
$fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
$total = isset($_POST['total']) ? floatval($_POST['total']) : 0;
$productos = isset($_POST['productos']) ? $_POST['productos'] : array();

// Validaciones básicas
if (empty($codigo)) {
    header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=El código de compra es obligatorio");
    exit;
}

if ($proveedor_id <= 0) {
    header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=Debe seleccionar un proveedor válido");
    exit;
}

if (empty($fecha)) {
    header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=La fecha de compra es obligatoria");
    exit;
}

// Validar formato de fecha
$fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$fecha_obj) {
    header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=El formato de fecha no es válido");
    exit;
}

if (empty($productos) || !is_array($productos)) {
    header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=Debe agregar al menos un producto a la compra");
    exit;
}

if ($total <= 0) {
    header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=El total de la compra debe ser mayor que cero");
    exit;
}

// Verificar que el código no esté duplicado (excluyendo la compra actual)
$sql_check = "SELECT compra_id FROM compra WHERE compra_codigo = ? AND compra_id != ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("si", $codigo, $compra_id);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();

if ($resultado_check->num_rows > 0) {
    header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=Ya existe otra compra con ese código");
    exit;
}

// Verificar que el proveedor existe y está activo
$sql_proveedor = "SELECT proveedor_id FROM proveedor WHERE proveedor_id = ? AND proveedor_estado = 1";
$stmt_proveedor = $conexion->prepare($sql_proveedor);
$stmt_proveedor->bind_param("i", $proveedor_id);
$stmt_proveedor->execute();

if ($stmt_proveedor->get_result()->num_rows === 0) {
    header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=El proveedor seleccionado no existe o está inactivo");
    exit;
}

// Validar y procesar productos
$productos_validados = array();
$total_calculado = 0;

foreach ($productos as $producto) {
    // Obtener datos del producto
    $producto_id = isset($producto['id']) ? intval($producto['id']) : 0;
    $cantidad = isset($producto['cantidad']) ? intval($producto['cantidad']) : 0;
    $precio_compra = isset($producto['precio']) ? floatval($producto['precio']) : 0;
    
    // Validar producto
    if ($producto_id <= 0) {
        header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=Todos los productos deben tener un ID válido");
        exit;
    }
    
    if ($cantidad <= 0) {
        header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=La cantidad de todos los productos debe ser mayor que cero");
        exit;
    }
    
    if ($precio_compra <= 0) {
        header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=El precio de compra de todos los productos debe ser mayor que cero");
        exit;
    }
    
    // Verificar que el producto existe
    $sql_producto = "SELECT producto_id FROM producto WHERE producto_id = ?";
    $stmt_producto = $conexion->prepare($sql_producto);
    $stmt_producto->bind_param("i", $producto_id);
    $stmt_producto->execute();
    
    if ($stmt_producto->get_result()->num_rows === 0) {
        header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=Uno de los productos seleccionados no existe");
        exit;
    }
    
    // Calcular subtotal
    $subtotal = $cantidad * $precio_compra;
    $total_calculado += $subtotal;
    
    // Agregar a productos validados
    $productos_validados[] = array(
        'producto_id' => $producto_id,
        'cantidad' => $cantidad,
        'precio_compra' => $precio_compra,
        'subtotal' => $subtotal
    );
}

// Verificar que el total calculado coincide con el total enviado (tolerancia de 1 peso)
if (abs($total_calculado - $total) > 1) {
    header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=El total calculado no coincide con el total enviado");
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Actualizar la compra principal
    $sql_compra = "UPDATE compra SET 
                        compra_codigo = ?, 
                        proveedor_id = ?, 
                        compra_fecha = ?, 
                        compra_total = ?, 
                        compra_observaciones = ?,
                        compra_fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE compra_id = ?";
    
    $stmt_compra = $conexion->prepare($sql_compra);
    $stmt_compra->bind_param("siidsi", $codigo, $proveedor_id, $fecha, $total, $observaciones, $compra_id);
    
    if (!$stmt_compra->execute()) {
        throw new Exception("Error al actualizar la compra: " . $conexion->error);
    }
    
    // 2. Eliminar todos los detalles existentes de la compra
    $sql_delete_detalles = "DELETE FROM detalle_compra WHERE compra_id = ?";
    $stmt_delete = $conexion->prepare($sql_delete_detalles);
    $stmt_delete->bind_param("i", $compra_id);
    
    if (!$stmt_delete->execute()) {
        throw new Exception("Error al eliminar detalles existentes: " . $conexion->error);
    }
    
    // 3. Insertar los nuevos detalles de la compra
    $sql_detalle = "INSERT INTO detalle_compra (
                        compra_id, 
                        producto_id, 
                        detalle_cantidad, 
                        detalle_precio_compra, 
                        detalle_subtotal
                    ) VALUES (?, ?, ?, ?, ?)";
    
    $stmt_detalle = $conexion->prepare($sql_detalle);
    
    foreach ($productos_validados as $producto) {
        $stmt_detalle->bind_param("iiidd", 
            $compra_id,
            $producto['producto_id'],
            $producto['cantidad'],
            $producto['precio_compra'],
            $producto['subtotal']
        );
        
        if (!$stmt_detalle->execute()) {
            throw new Exception("Error al insertar detalle de producto: " . $conexion->error);
        }
    }
    
    // Confirmar transacción
    $conexion->commit();
    
    // Redirigir a la vista de la compra con mensaje de éxito
    header("Location: ../views/compras/compra_ver.php?id=$compra_id&mensaje=Compra actualizada correctamente&tipo=exito");
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->rollback();
    
    // Redirigir al formulario con mensaje de error
    header("Location: ../views/compras/compra_editar.php?id=$compra_id&error=" . urlencode($e->getMessage()));
    exit;
}