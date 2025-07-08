<?php
/**
 * GESTOR DE FACTURAS EXISTENTES
 * 
 * Este archivo permite ver, buscar y generar PDFs de facturas
 * que ya han sido creadas en el sistema.
 * 
 * Funcionalidades:
 * - Listar todas las facturas
 * - Buscar facturas por cliente o fecha
 * - Generar PDF de facturas existentes
 * - Ver detalles de facturas
 */

// Iniciar sesión
session_start();

// Incluir archivos de configuración
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';

// TODO: Descomentar cuando se implemente la autenticación
// require_once __DIR__ . '/config/auth.php';

// --- PROCESAMIENTO DE BÚSQUEDA ---

$busqueda = '';
$fecha_desde = '';
$fecha_hasta = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $busqueda = trim($_POST['busqueda'] ?? '');
    $fecha_desde = $_POST['fecha_desde'] ?? '';
    $fecha_hasta = $_POST['fecha_hasta'] ?? '';
}

// --- CONSULTA DE FACTURAS ---

try {
    // Construir la consulta base
    $sql = "SELECT f.id, f.identificacion_cliente, f.fecha, f.total, 
                   COUNT(d.id) as total_productos
            FROM facturas f 
            LEFT JOIN detalle_factura d ON f.id = d.id_factura";
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    // Agregar condiciones de búsqueda
    if (!empty($busqueda)) {
        $where_conditions[] = "f.identificacion_cliente LIKE ?";
        $params[] = "%$busqueda%";
        $types .= 's';
    }
    
    if (!empty($fecha_desde)) {
        $where_conditions[] = "DATE(f.fecha) >= ?";
        $params[] = $fecha_desde;
        $types .= 's';
    }
    
    if (!empty($fecha_hasta)) {
        $where_conditions[] = "DATE(f.fecha) <= ?";
        $params[] = $fecha_hasta;
        $types .= 's';
    }
    
    // Agregar WHERE si hay condiciones
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    
    // Agrupar y ordenar
    $sql .= " GROUP BY f.id ORDER BY f.fecha DESC";
    
    // Preparar y ejecutar la consulta
    $stmt = $conexion->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $facturas = $stmt->get_result();
    
} catch (Exception $e) {
    $mensaje_error = "Error al cargar las facturas: " . $e->getMessage();
    $facturas = null;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Facturas - Sistema de Inventario</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Gestión de Facturas</h1>
            <p class="text-muted">Consulta y genera PDFs de facturas existentes</p>
        </div>
        <div class="d-flex gap-2">
            <a href="factura.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nueva Factura
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-house"></i> Dashboard
            </a>
        </div>
    </div>
    
    <!-- Filtros de búsqueda -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-4">
                    <label for="busqueda" class="form-label">Buscar por Cliente</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           value="<?php echo htmlspecialchars($busqueda); ?>" 
                           placeholder="Identificación del cliente">
                </div>
                <div class="col-md-3">
                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                           value="<?php echo htmlspecialchars($fecha_desde); ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                           value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <a href="facturas_listar.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de facturas -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Facturas Registradas</h5>
        </div>
        <div class="card-body">
            <?php if ($facturas && $facturas->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Factura #</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Productos</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($factura = $facturas->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong>FAC-<?php echo str_pad($factura['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($factura['identificacion_cliente']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($factura['fecha'])); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $factura['total_productos']; ?> productos
                                        </span>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($factura['total'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="generar_pdf.php?id_factura=<?php echo $factura['id']; ?>" 
                                               class="btn btn-primary btn-sm" 
                                               title="Descargar PDF">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-info btn-sm" 
                                                    onclick="verDetalles(<?php echo $factura['id']; ?>)"
                                                    title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Estadísticas -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4><?php echo $facturas->num_rows; ?></h4>
                                <p class="mb-0">Total Facturas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4>$<?php 
                                    $facturas->data_seek(0);
                                    $total_ventas = 0;
                                    while ($f = $facturas->fetch_assoc()) {
                                        $total_ventas += $f['total'];
                                    }
                                    echo number_format($total_ventas, 2);
                                ?></h4>
                                <p class="mb-0">Total Ventas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4><?php echo date('d/m/Y'); ?></h4>
                                <p class="mb-0">Fecha Actual</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-receipt display-1 text-muted"></i>
                    <h3 class="text-muted mt-3">No se encontraron facturas</h3>
                    <p class="text-muted">
                        <?php if (!empty($busqueda) || !empty($fecha_desde) || !empty($fecha_hasta)): ?>
                            No hay facturas que coincidan con los filtros aplicados.
                        <?php else: ?>
                            Aún no se han generado facturas en el sistema.
                        <?php endif; ?>
                    </p>
                    <a href="factura.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Crear Primera Factura
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de factura -->
<div class="modal fade" id="detallesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de la Factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detallesContent">
                <!-- El contenido se cargará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="btnDescargarPDF" class="btn btn-primary">
                    <i class="bi bi-download"></i> Descargar PDF
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Scripts de Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function verDetalles(idFactura) {
    // Cargar detalles de la factura via AJAX
    fetch(`factura_detalles.php?id_factura=${idFactura}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('detallesContent').innerHTML = html;
            document.getElementById('btnDescargarPDF').href = `generar_pdf.php?id_factura=${idFactura}`;
            new bootstrap.Modal(document.getElementById('detallesModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los detalles de la factura');
        });
}
</script>
</body>
</html> 