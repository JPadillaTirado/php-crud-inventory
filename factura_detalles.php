<?php
/**
 * DETALLES DE FACTURA
 * 
 * Este archivo muestra los detalles completos de una factura específica.
 * Es llamado por AJAX desde facturas_listar.php para mostrar los detalles
 * en un modal.
 */

// Incluir archivos de configuración
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';

// Verificar que se haya pasado un ID de factura válido
if (!isset($_GET['id_factura']) || !is_numeric($_GET['id_factura'])) {
    echo "<div class='alert alert-danger'>ID de factura inválido.</div>";
    exit;
}

$id_factura = (int)$_GET['id_factura'];

try {
    // Consultar los datos principales de la factura
    $stmt_factura = $conexion->prepare("
        SELECT id, identificacion_cliente, fecha, total 
        FROM facturas 
        WHERE id = ?
    ");
    
    $stmt_factura->bind_param("i", $id_factura);
    $stmt_factura->execute();
    $resultado_factura = $stmt_factura->get_result();
    $factura = $resultado_factura->fetch_assoc();

    if (!$factura) {
        echo "<div class='alert alert-danger'>La factura no existe.</div>";
        exit;
    }

    // Consultar los detalles de la factura
    $stmt_detalle = $conexion->prepare("
        SELECT 
            d.cantidad,
            d.precio_unitario,
            d.impuesto,
            p.producto_codigo,
            p.producto_nombre
        FROM detalle_factura d 
        JOIN producto p ON d.id_producto = p.producto_id 
        WHERE d.id_factura = ?
        ORDER BY p.producto_nombre
    ");
    
    $stmt_detalle->bind_param("i", $id_factura);
    $stmt_detalle->execute();
    $detalles = $stmt_detalle->get_result();

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error al cargar los detalles: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<!-- Información de la factura -->
<div class="row mb-4">
    <div class="col-md-6">
        <h6 class="text-muted">Información de la Factura</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Número:</strong></td>
                <td>FAC-<?php echo str_pad($factura['id'], 6, '0', STR_PAD_LEFT); ?></td>
            </tr>
            <tr>
                <td><strong>Cliente:</strong></td>
                <td><?php echo htmlspecialchars($factura['identificacion_cliente']); ?></td>
            </tr>
            <tr>
                <td><strong>Fecha:</strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($factura['fecha'])); ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="text-muted">Resumen</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Total Productos:</strong></td>
                <td><?php echo $detalles->num_rows; ?></td>
            </tr>
            <tr>
                <td><strong>Total Factura:</strong></td>
                <td><strong class="text-success">$<?php echo number_format($factura['total'], 2); ?></strong></td>
            </tr>
        </table>
    </div>
</div>

<!-- Tabla de productos -->
<h6 class="text-muted mb-3">Productos de la Factura</h6>
<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="text-center">Cantidad</th>
                <th class="text-end">Precio Unit.</th>
                <th class="text-end">Impuesto</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal_sin_impuesto = 0;
            $total_impuestos = 0;
            
            while ($detalle = $detalles->fetch_assoc()): 
                $subtotal_item = $detalle['cantidad'] * $detalle['precio_unitario'];
                $impuesto_item = $subtotal_item * ($detalle['impuesto'] / 100);
                $total_item = $subtotal_item + $impuesto_item;
                
                $subtotal_sin_impuesto += $subtotal_item;
                $total_impuestos += $impuesto_item;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($detalle['producto_codigo']); ?></td>
                    <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                    <td class="text-center"><?php echo $detalle['cantidad']; ?></td>
                    <td class="text-end">$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                    <td class="text-end">$<?php echo number_format($impuesto_item, 2); ?></td>
                    <td class="text-end"><strong>$<?php echo number_format($total_item, 2); ?></strong></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                <td class="text-end">$<?php echo number_format($subtotal_sin_impuesto, 2); ?></td>
            </tr>
            <tr>
                <td colspan="5" class="text-end"><strong>Impuestos (19%):</strong></td>
                <td class="text-end">$<?php echo number_format($total_impuestos, 2); ?></td>
            </tr>
            <tr class="table-success">
                <td colspan="5" class="text-end"><strong>TOTAL:</strong></td>
                <td class="text-end"><strong>$<?php echo number_format($factura['total'], 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Información adicional -->
<div class="mt-3">
    <small class="text-muted">
        <i class="bi bi-info-circle"></i>
        Esta factura fue generada el <?php echo date('d/m/Y \a \l\a\s H:i:s', strtotime($factura['fecha'])); ?>
    </small>
</div> 