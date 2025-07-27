<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: ../views/compras/compra_nuevo.php?error=Error de conexión a la base de datos');
    exit;
}

// Verificar que el formulario haya sido enviado por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/compras/compra_nuevo.php?error=Método no permitido');
    exit;
}

// Obtener y validar datos básicos del formulario
$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
$proveedor_id = isset($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : 0;
$fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
$total = isset($_POST['total']) ? floatval($_POST['total']) : 0;
$productos = isset($_POST['productos']) ? $_POST['productos'] : array();

// Obtener ID del usuario actual (deberías tener esto en tu sistema de autenticación)
// Por ahora uso un valor fijo, pero debes cambiarlo por el usuario logueado
$usuario_id = 1; // TODO: Obtener del sistema de autenticación

// Validaciones básicas
if (empty($codigo)) {
    header('Location: ../views/compras/compra_nuevo.php?error=El código de compra es obligatorio');
    exit;
}

if ($proveedor_id <= 0) {
    header('Location: ../views/compras/compra_nuevo.php?error=Debe seleccionar un proveedor válido');
    exit;
}

if (empty($fecha)) {
    header('Location: ../views/compras/compra_nuevo.php?error=La fecha de compra es obligatoria');
    exit;
}

// Validar formato de fecha
$fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$fecha_obj) {
    header('Location: ../views/compras/compra_nuevo.php?error=El formato de fecha no es válido');
    exit;
}

if (empty($productos) || !is_array($productos)) {
    header('Location: ../views/compras/compra_nuevo.php?error=Debe agregar al menos un producto a la compra');
    exit;
}

if ($total <= 0) {
    header('Location: ../views/compras/compra_nuevo.php?error=El total de la compra debe ser mayor que cero');
    exit;
}

// Verificar que el código no esté duplicado
$sql_check = "SELECT compra_id FROM compra WHERE compra_codigo = ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("s", $codigo);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();

if ($resultado_check->num_rows > 0) {
    header('Location: ../views/compras/compra_nuevo.php?error=Ya existe una compra con ese código');
    exit;
}

// Verificar que el proveedor existe y está activo
$sql_proveedor = "SELECT proveedor_id FROM proveedor WHERE proveedor_id = ? AND proveedor_estado = 1";
$stmt_proveedor = $conexion->prepare($sql_proveedor);
$stmt_proveedor->bind_param("i", $proveedor_id);
$stmt_proveedor->execute();

if ($stmt_proveedor->get_result()->num_rows === 0) {
    header('Location: ../views/compras/compra_nuevo.php?error=El proveedor seleccionado no existe o está inactivo');
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
        header('Location: ../views/compras/compra_nuevo.php?error=Todos los productos deben tener un ID válido');
        exit;
    }
    
    if ($cantidad <= 0) {
        header('Location: ../views/compras/compra_nuevo.php?error=La cantidad de todos los productos debe ser mayor que cero');
        exit;
    }
    
    if ($precio_compra <= 0) {
        header('Location: ../views/compras/compra_nuevo.php?error=El precio de compra de todos los productos debe ser mayor que cero');
        exit;
    }
    
    // Verificar que el producto existe
    $sql_producto = "SELECT producto_id FROM producto WHERE producto_id = ?";
    $stmt_producto = $conexion->prepare($sql_producto);
    $stmt_producto->bind_param("i", $producto_id);
    $stmt_producto->execute();
    
    if ($stmt_producto->get_result()->num_rows === 0) {
        header('Location: ../views/compras/compra_nuevo.php?error=Uno de los productos seleccionados no existe');
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
    header('Location: ../views/compras/compra_nuevo.php?error=El total calculado no coincide con el total enviado');
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Insertar la compra principal
    $sql_compra = "INSERT INTO compra (
                        compra_codigo, 
                        proveedor_id, 
                        usuario_id, 
                        compra_fecha, 
                        compra_total, 
                        compra_estado, 
                        compra_observaciones
                    ) VALUES (?, ?, ?, ?, ?, 'pendiente', ?)";
    
    $stmt_compra = $conexion->prepare($sql_compra);
    $stmt_compra->bind_param("siiids", $codigo, $proveedor_id, $usuario_id, $fecha, $total, $observaciones);
    
    if (!$stmt_compra->execute()) {
        throw new Exception("Error al insertar la compra: " . $conexion->error);
    }
    
    // Obtener el ID de la compra recién creada
    $compra_id = $conexion->insert_id;
    
    // 2. Insertar los detalles de la compra
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
    
    // Redirigir al listado con mensaje de éxito
    header("Location: ../views/compras/compra_ver.php?id=$compra_id&mensaje=Compra creada correctamente&tipo=exito");
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->rollback();
    
    // Redirigir al formulario con mensaje de error
    header('Location: ../views/compras/compra_nuevo.php?error=' . urlencode($e->getMessage()));
    exit;
}