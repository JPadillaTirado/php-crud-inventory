<?php
/**
 * SISTEMA DE FACTURACIÓN
 * 
 * Este archivo maneja la creación de facturas en el sistema de inventario.
 * Permite agregar productos a una factura temporal (en sesión) y luego
 * finalizar la venta guardando los datos en la base de datos.
 * 
 * Funcionalidades:
 * - Agregar productos a la factura
 * - Calcular totales con impuestos
 * - Finalizar venta y guardar en BD
 * - Cancelar factura
 * - Generar PDF de la factura
 */

// Inicia la sesión para mantener los productos de la factura
session_start();

// Incluir archivos de configuración
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';

// TODO: Descomentar cuando se implemente la autenticación
// require_once __DIR__ . '/config/auth.php';

// --- LÓGICA DE PROCESAMIENTO DE FORMULARIOS ---

// Verificamos si se envió una acción desde un formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ACCIÓN 1: Agregar un producto a la factura
    if (isset($_POST['accion']) && $_POST['accion'] == 'agregar') {
        
        // Validar y sanitizar datos de entrada
        $id_producto = filter_var($_POST['producto_id'], FILTER_VALIDATE_INT);
        $cantidad = filter_var($_POST['cantidad'], FILTER_VALIDATE_INT);
        
        // Verificar que los datos sean válidos
        if ($id_producto && $cantidad && $cantidad > 0) {
            
            // Impuesto fijo del 19% (se puede hacer configurable)
            $impuesto = 19;
            
            // Buscar los datos del producto en la base de datos
            // NOTA: Corregido para usar los nombres correctos de columnas según schema.sql
            $stmt = $conexion->prepare("SELECT producto_id, producto_nombre, producto_precio, producto_stock FROM producto WHERE producto_id = ?");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $producto = $resultado->fetch_assoc();

            // Verificar si el producto existe y hay stock suficiente
            if ($producto && $cantidad <= $producto['producto_stock']) {
                
                // Crear array con la información del producto para la factura
                $item = [
                    "id" => $producto['producto_id'],
                    "nombre" => $producto['producto_nombre'],
                    "precio" => $producto['producto_precio'],
                    "cantidad" => $cantidad,
                    "impuesto" => $impuesto
                ];

                // Inicializar array de factura si no existe
                if (!isset($_SESSION['factura_items'])) {
                    $_SESSION['factura_items'] = [];
                }

                // Agregar el item a la sesión
                $_SESSION['factura_items'][] = $item;
                
                // Mensaje de éxito
                $mensaje_exito = "Producto agregado correctamente.";
                
            } else {
                $mensaje_error = "No hay stock suficiente para este producto o el producto no existe.";
            }
        } else {
            $mensaje_error = "Datos de entrada inválidos.";
        }
    }

    // ACCIÓN 2: Finalizar la venta y guardar en la base de datos
    if (isset($_POST['accion']) && $_POST['accion'] == 'finalizar') {
        
        // Validar identificación del cliente
        $identificacion_cliente = trim($_POST['identificacion_cliente']);
        
        // Verificar que haya datos válidos
        if (!empty($identificacion_cliente) && !empty($_SESSION['factura_items'])) {
            
            try {
                // Iniciar transacción para asegurar integridad de datos
                $conexion->begin_transaction();
                
                $total_factura = 0;
                
                // Calcular el total de la factura
                foreach ($_SESSION['factura_items'] as $item) {
                    $subtotal = $item['precio'] * $item['cantidad'];
                    $total_factura += $subtotal + ($subtotal * ($item['impuesto'] / 100));
                }

                // 1. Insertar la factura en la tabla 'facturas'
                $stmt = $conexion->prepare("INSERT INTO facturas (identificacion_cliente, total) VALUES (?, ?)");
                $stmt->bind_param("sd", $identificacion_cliente, $total_factura);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al crear la factura: " . $stmt->error);
                }
                
                $id_factura = $conexion->insert_id; // Obtener el ID de la factura recién creada

                // 2. Insertar cada producto en 'detalle_factura' y actualizar el stock
                foreach ($_SESSION['factura_items'] as $item) {
                    
                    // Insertar detalle de la factura
                    $stmt_detalle = $conexion->prepare("INSERT INTO detalle_factura (id_factura, id_producto, cantidad, precio_unitario, impuesto) VALUES (?, ?, ?, ?, ?)");
                    $stmt_detalle->bind_param("iiidd", $id_factura, $item['id'], $item['cantidad'], $item['precio'], $item['impuesto']);
                    
                    if (!$stmt_detalle->execute()) {
                        throw new Exception("Error al guardar detalle de factura: " . $stmt_detalle->error);
                    }

                    // Actualizar stock del producto
                    $stmt_stock = $conexion->prepare("UPDATE producto SET producto_stock = producto_stock - ? WHERE producto_id = ?");
                    $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
                    
                    if (!$stmt_stock->execute()) {
                        throw new Exception("Error al actualizar stock: " . $stmt_stock->error);
                    }
                }

                // Confirmar transacción
                $conexion->commit();
                
                // Guardar el ID de factura en sesión para mostrar mensaje de confirmación
                $_SESSION['factura_generada'] = $id_factura;
                
                // Limpiar la sesión de items y redirigir
                unset($_SESSION['factura_items']);
                header("Location: factura.php?success=1");
                exit();

            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $conexion->rollback();
                $mensaje_error = "Error al procesar la factura: " . $e->getMessage();
            }

        } else {
            $mensaje_error = "Asegúrate de ingresar la identificación del cliente y agregar al menos un producto.";
        }
    }
    
    // ACCIÓN 3: Cancelar la factura
    if (isset($_POST['accion']) && $_POST['accion'] == 'cancelar') {
        unset($_SESSION['factura_items']);
        $mensaje_exito = "Factura cancelada correctamente.";
    }
}

// Obtener todos los productos disponibles para el menú desplegable
// Solo productos con stock mayor a 0
$productos_disponibles = $conexion->query("SELECT producto_id, producto_nombre, producto_stock, producto_precio FROM producto WHERE producto_stock > 0 ORDER BY producto_nombre");

if (!$productos_disponibles) {
    $mensaje_error = "Error al cargar productos: " . $conexion->error;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Facturación - Nueva Factura</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<div class="container mt-5">
    
    <!-- Mensajes de alerta -->
    <?php if (isset($mensaje_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($mensaje_exito)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje_exito); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Mensaje de confirmación de factura generada -->
    <?php if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_SESSION['factura_generada'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>¡Factura generada exitosamente!</strong><br>
                    La factura #<?php echo str_pad($_SESSION['factura_generada'], 6, '0', STR_PAD_LEFT); ?> ha sido creada y guardada en la base de datos.
                </div>
                <div class="d-flex gap-2">
                    <a href="generar_pdf.php?id_factura=<?php echo $_SESSION['factura_generada']; ?>" 
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-download"></i> Descargar PDF
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['factura_generada']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Nueva Factura</h1>
        <a href="dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
    </div>
    
    <!-- Formulario para agregar productos -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Agregar Producto</h5>
        </div>
        <div class="card-body">
            <form action="factura.php" method="post">
                <input type="hidden" name="accion" value="agregar">
                <div class="row">
                    <div class="col-md-6">
                        <label for="producto_id" class="form-label">Producto</label>
                        <select name="producto_id" class="form-select" required>
                            <option value="">Selecciona un producto</option>
                            <?php if ($productos_disponibles): ?>
                                <?php while ($producto = $productos_disponibles->fetch_assoc()): ?>
                                    <option value="<?php echo $producto['producto_id']; ?>">
                                        <?php echo htmlspecialchars($producto['producto_nombre']) . 
                                              " - Stock: " . $producto['producto_stock'] . 
                                              " - Precio: $" . number_format($producto['producto_precio'], 2); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" name="cantidad" value="1" min="1" class="form-control" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Agregar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen de la factura -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Resumen de la Factura</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Impuesto (19%)</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($_SESSION['factura_items'])): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <em>Aún no has agregado productos a la factura.</em>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $total_final = 0;
                            foreach ($_SESSION['factura_items'] as $item): 
                                $subtotal = $item['precio'] * $item['cantidad'];
                                $subtotal_con_impuesto = $subtotal + ($subtotal * ($item['impuesto'] / 100));
                                $total_final += $subtotal_con_impuesto;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                    <td><?php echo $item['cantidad']; ?></td>
                                    <td>$<?php echo number_format($item['precio'], 2); ?></td>
                                    <td>$<?php echo number_format($subtotal * ($item['impuesto'] / 100), 2); ?></td>
                                    <td>$<?php echo number_format($subtotal_con_impuesto, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <?php if (!empty($_SESSION['factura_items'])): ?>
                            <tr class="table-success">
                                <th colspan="4" class="text-end">TOTAL:</th>
                                <th>$<?php echo number_format($total_final, 2); ?></th>
                            </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Botones de acción -->
    <div class="d-flex justify-content-between">
        <form action="factura.php" method="post" class="d-inline">
            <input type="hidden" name="accion" value="cancelar">
            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que quieres cancelar la factura?')">
                <i class="bi bi-x-circle"></i> Cancelar Factura
            </button>
        </form>
        
        <?php if (!empty($_SESSION['factura_items'])): ?>
            <form action="factura.php" method="post" class="d-inline">
                <input type="hidden" name="accion" value="finalizar">
                <div class="input-group" style="width: 400px;">
                    <input type="text" name="identificacion_cliente" class="form-control" 
                           placeholder="Identificación del Cliente" required 
                           pattern="[0-9A-Za-z]{3,20}" 
                           title="Ingresa una identificación válida (3-20 caracteres)">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Finalizar y Generar Factura
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts de Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
