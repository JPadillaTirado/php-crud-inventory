<?php
// index.php

// Inicia la sesión. Es crucial para guardar los productos de la factura.
session_start();
require 'conexion.php';

// --- LÓGICA DE PROCESAMIENTO ---

// Verificamos si se envió una acción desde un formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Acción 1: Agregar un producto a la factura
    if (isset($_POST['accion']) && $_POST['accion'] == 'agregar') {
        $id_producto = $_POST['producto_id'];
        $cantidad = $_POST['cantidad'];
        $impuesto = $_POST['producto_impuesto']; // Impuesto fijo del 19% para este ejemplo

        // Buscamos los datos del producto en la BD
        $stmt = $conexion->prepare("SELECT nombre, precio, stock FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $producto = $resultado->fetch_assoc();

        // Verificamos si hay stock suficiente
        if ($producto && $cantidad <= $producto['stock']) {
            // Creamos un array con la información del producto para la factura
            $item = [
                "id" => $id_producto,
                "nombre" => $producto['nombre'],
                "precio" => $producto['precio'],
                "cantidad" => $cantidad,
                "impuesto" => $impuesto
            ];

            // Agregamos el item a la sesión
            $_SESSION['factura_items'][] = $item;
        } else {
            echo "<script>alert('No hay stock suficiente para este producto.');</script>";
        }
    }

    // Acción 2: Finalizar la venta y guardar en la BD
    if (isset($_POST['accion']) && $_POST['accion'] == 'finalizar') {
        $identificacion_cliente = $_POST['identificacion_cliente'];
        
        if (!empty($identificacion_cliente) && !empty($_SESSION['factura_items'])) {
            $total_factura = 0;
            // Calculamos el total de la factura
            foreach ($_SESSION['factura_items'] as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $total_factura += $subtotal + ($subtotal * ($item['impuesto'] / 100));
            }

            // 1. Insertar la factura en la tabla 'facturas'
            $stmt = $conexion->prepare("INSERT INTO facturas (identificacion_cliente, total) VALUES (?, ?)");
            $stmt->bind_param("sd", $identificacion_cliente, $total_factura);
            $stmt->execute();
            $id_factura = $conexion->insert_id; // Obtenemos el ID de la factura recién creada

            // 2. Insertar cada producto en 'detalle_factura' y actualizar el stock
            foreach ($_SESSION['factura_items'] as $item) {
                // Insertar detalle
                $stmt_detalle = $conexion->prepare("INSERT INTO detalle_factura (id_factura, id_producto, cantidad, precio_unitario, impuesto) VALUES (?, ?, ?, ?, ?)");
                $stmt_detalle->bind_param("iiidd", $id_factura, $item['id'], $item['cantidad'], $item['precio'], $item['impuesto']);
                $stmt_detalle->execute();

                // Actualizar stock
                $stmt_stock = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
                $stmt_stock->execute();
            }

            // Limpiamos la sesión y redirigimos para generar el PDF
            unset($_SESSION['factura_items']);
            header("Location: generar_pdf.php?id_factura=" . $id_factura);
            exit();

        } else {
            echo "<script>alert('Asegúrate de ingresar la identificación del cliente y agregar al menos un producto.');</script>";
        }
    }
    
    // Acción 3: Cancelar la factura
    if (isset($_POST['accion']) && $_POST['accion'] == 'cancelar') {
        unset($_SESSION['factura_items']);
    }
}

// Obtenemos todos los productos de la BD para el menú desplegable
$productos_disponibles = $conexion->query("SELECT id, nombre, stock FROM productos WHERE stock > 0");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturación Sencilla</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Nueva Factura</h1>
    
    <div class="card mb-4">
        <div class="card-header">Agregar Producto</div>
        <div class="card-body">
            <form action="factura.php" method="post">
                <input type="hidden" name="accion" value="agregar">
                <div class="row">
                    <div class="col-md-6">
                        <label for="producto_id" class="form-label">Producto</label>
                        <select name="producto_id" class="form-select" required>
                            <option value="">Selecciona un producto</option>
                            <?php while ($producto = $productos_disponibles->fetch_assoc()): ?>
                                <option value="<?php echo $producto['id']; ?>">
                                    <?php echo htmlspecialchars($producto['nombre']) . " (Stock: " . $producto['stock'] . ")"; ?>
                                </option>
                            <?php endwhile; ?>
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

    <h2 class="h4">Resumen de la Factura</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unit.</th>
                <th>Impuesto (%)</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($_SESSION['factura_items'])): ?>
                <tr>
                    <td colspan="5" class="text-center">Aún no has agregado productos.</td>
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
                        <td><?php echo $item['impuesto']; ?>%</td>
                        <td>$<?php echo number_format($subtotal_con_impuesto, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <?php if (!empty($_SESSION['factura_items'])): ?>
                <tr>
                    <th colspan="4" class="text-end">TOTAL:</th>
                    <th>$<?php echo number_format($total_final, 2); ?></th>
                </tr>
            <?php endif; ?>
        </tfoot>
    </table>
    
    <div class="d-flex justify-content-between">
        <form action="factura.php" method="post">
            <input type="hidden" name="accion" value="cancelar">
            <button type="submit" class="btn btn-danger">Cancelar Factura</button>
        </form>
        
        <form action="factura.php" method="post">
            <input type="hidden" name="accion" value="finalizar">
            <div class="input-group">
                <input type="text" name="identificacion_cliente" class="form-control" placeholder="Identificación del Cliente" required>
                <button type="submit" class="btn btn-success">Finalizar y Generar Factura</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
