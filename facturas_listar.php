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
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Gestión de Facturas - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        /* Ajustes específicos para facturas */
        .dashboard-container {
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        
        .main-content {
            padding-top: 0;
        }
        
        .facturas-header {
            background-color: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .facturas-content {
            padding: 30px;
        }
        
        /* Reemplazar estilos de Bootstrap con los del dashboard */
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-radius: 8px 8px 0 0;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            margin: -20px -20px 20px -20px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 0;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #2980b9;
            box-shadow: 0 0 0 0.2rem rgba(41, 128, 185, 0.25);
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .btn-primary {
            background-color: #2980b9;
            border-color: #2980b9;
            border-radius: 6px;
            font-weight: 500;
            padding: 10px 20px;
        }
        
        .btn-success {
            background-color: #27ae60;
            border-color: #27ae60;
            border-radius: 6px;
            font-weight: 500;
            padding: 10px 20px;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 6px;
            font-weight: 500;
            padding: 10px 20px;
        }
        
        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
            border-radius: 6px;
            font-weight: 500;
            padding: 10px 15px;
        }
        
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }
        
        .table {
            margin-bottom: 0;
            background-color: white;
        }
        
        .table thead th {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 15px;
            font-weight: 500;
        }
        
        .table tbody td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .bg-info {
            background-color: #17a2b8 !important;
        }
        
        .btn-group .btn {
            margin-right: 5px;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
        }
        
        /* Estadísticas */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, #17a2b8, #20c997);
        }
        
        .stat-card h4 {
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state .icon {
            font-size: 72px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: #495057;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .empty-state p {
            color: #6c757d;
            margin-bottom: 30px;
        }
        
        /* Mensajes */
        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .mensaje.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        /* Modal adjustments */
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            border-radius: 10px 10px 0 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .facturas-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .facturas-content {
                padding: 20px;
            }
            
            .table {
                font-size: 14px;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .form-group.row {
                flex-direction: column;
            }
            
            .d-flex.gap-2 {
                width: 100%;
            }
        }
        
        /* Form styling */
        .row.g-3 {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .col-md-4, .col-md-3, .col-md-2 {
            flex: 1;
            min-width: 200px;
        }
        
        .d-flex.gap-2 {
            display: flex;
            gap: 10px;
        }
        
        .flex-fill {
            flex: 1;
        }
        
        .w-100 {
            width: 100%;
        }
        
        .d-flex.align-items-end {
            display: flex;
            align-items: flex-end;
        }
    
    /* Agregar al <style> de tu documento */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1050;
            display: none; /* Oculto por defecto */
        }

        .modal-dialog {
            position: relative;
            margin: 1.75rem auto;
            max-width: 800px;
            width: calc(100% - 2rem);
        }

        .modal-content {
            position: relative;
            background-color: white;
            border: 1px solid rgba(0,0,0,.2);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
            outline: 0;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            border-radius: 10px 10px 0 0;
        }

        .modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: 1rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 10px 10px;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.5;
        }

        .btn-close:hover {
            opacity: 1;
        }

    
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-icon">📦</span>
                    <span class="logo-text">Inventory</span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">☰</button>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item" title="Dashboard">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="views/productos/producto_listar.php" class="nav-item" title="Productos">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Productos</span>
                </a>
                <a href="facturas_listar.php" class="nav-item active">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-text">Facturación</span>
                </a>
                <a href="en_construccion.php?modulo=configuraciones" class="nav-item" title="Configuraciones">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Configuraciones</span>
                </a>
                <a href="views/usuarios/usuario_listar.php" class="nav-item" title="Usuarios">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Usuarios</span>
                </a>
                <a href="en_construccion.php?modulo=proveedores" class="nav-item" title="Proveedores">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="en_construccion.php?modulo=Compras" class="nav-item" title="Compras">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Compras</span>
                </a>
                <a href="views/categorias/categoria_listar.php" class="nav-item" title="Categorías">
                    <span class="nav-icon">📁</span>
                    <span class="nav-text">Categoría</span>
                </a>
                <a href="en_construccion.php?modulo=informes" class="nav-item" title="Informes">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Informes</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Header de la página -->
            <div class="facturas-header">
                <div>
                    <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">Gestión de Facturas</h1>
                    <p style="color: #6c757d; font-size: 16px;">Consulta y genera PDFs de facturas existentes</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="factura.php" class="btn btn-success">
                        <span style="margin-right: 5px;">+</span> Nueva Factura
                    </a>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="facturas-content">
                <!-- Mensajes de alerta -->
                <?php if (isset($mensaje_error)): ?>
                    <div class="mensaje error">
                        <?php echo htmlspecialchars($mensaje_error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filtros de búsqueda -->
                <div class="card">
                    <div class="card-header">
                        <h5 style="margin: 0;">Filtros de Búsqueda</h5>
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
                                        🔍 Buscar
                                    </button>
                                    <a href="facturas_listar.php" class="btn btn-outline-secondary">
                                        🔄
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de facturas -->
                <div class="card">
                    <div class="card-header">
                        <h5 style="margin: 0;">Facturas Registradas</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($facturas && $facturas->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
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
                                                    <div class="btn-group">
                                                        <a href="generar_pdf.php?id_factura=<?php echo $factura['id']; ?>" 
                                                           class="btn btn-primary btn-sm" 
                                                           title="Descargar PDF">
                                                            📄 PDF
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-info btn-sm" 
                                                                onclick="verDetalles(<?php echo $factura['id']; ?>)"
                                                                title="Ver detalles">
                                                            👁️ Ver
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Estadísticas -->
                            <div class="stats-cards">
                                <div class="stat-card">
                                    <h4><?php echo $facturas->num_rows; ?></h4>
                                    <p>Total Facturas</p>
                                </div>
                                <div class="stat-card success">
                                    <h4>$<?php 
                                        $facturas->data_seek(0);
                                        $total_ventas = 0;
                                        while ($f = $facturas->fetch_assoc()) {
                                            $total_ventas += $f['total'];
                                        }
                                        echo number_format($total_ventas, 2);
                                    ?></h4>
                                    <p>Total Ventas</p>
                                </div>
                                <div class="stat-card info">
                                    <h4><?php echo date('d/m/Y'); ?></h4>
                                    <p>Fecha Actual</p>
                                </div>
                            </div>
                            
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="icon">🧾</div>
                                <h3>No se encontraron facturas</h3>
                                <p>
                                    <?php if (!empty($busqueda) || !empty($fecha_desde) || !empty($fecha_hasta)): ?>
                                        No hay facturas que coincidan con los filtros aplicados.
                                    <?php else: ?>
                                        Aún no se han generado facturas en el sistema.
                                    <?php endif; ?>
                                </p>
                                <a href="factura.php" class="btn btn-primary">
                                    <span style="margin-right: 5px;">+</span> Crear Primera Factura
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para ver detalles de factura -->
    <div class="modal fade" id="detallesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de la Factura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="cerrarModal()">&times;</button>
                </div>
                <div class="modal-body" id="detallesContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cerrar</button>
                    <a href="#" id="btnDescargarPDF" class="btn btn-primary">
                        📄 Descargar PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para ver detalles de factura
        function verDetalles(idFactura) {
            // Cargar detalles de la factura via AJAX
            fetch(`factura_detalles.php?id_factura=${idFactura}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detallesContent').innerHTML = html;
                    document.getElementById('btnDescargarPDF').href = `generar_pdf.php?id_factura=${idFactura}`;
                    document.getElementById('detallesModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los detalles de la factura');
                });
        }

        // Función para cerrar modal
        function cerrarModal() {
            document.getElementById('detallesModal').style.display = 'none';
        }

        // Script para el manejo del sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mainContent = document.querySelector('.main-content');
            
            // Función para alternar el sidebar
            function toggleSidebar() {
                sidebar.classList.toggle('collapsed');
                
                if (sidebar.classList.contains('collapsed')) {
                    mainContent.classList.add('expanded');
                } else {
                    mainContent.classList.remove('expanded');
                }
                
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
                
                window.dispatchEvent(new Event('sidebarToggled'));
            }
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            
            // Restaurar el estado del sidebar
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
            
            // Manejo del sidebar en dispositivos móviles
            let touchStartX = 0;
            let touchEndX = 0;
            
            document.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            });
            
            document.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            });
            
            function handleSwipe() {
                if (touchEndX > touchStartX + 50 && sidebar.classList.contains('collapsed')) {
                    toggleSidebar();
                }
                if (touchEndX < touchStartX - 50 && !sidebar.classList.contains('collapsed')) {
                    toggleSidebar();
                }
            }
            
            // Cerrar sidebar al hacer clic fuera en móviles
            if (window.innerWidth <= 768) {
                document.addEventListener('click', function(e) {
                    const isClickInsideSidebar = sidebar.contains(e.target);
                    const isClickOnToggle = sidebarToggle.contains(e.target);
                    
                    if (!isClickInsideSidebar && !isClickOnToggle && !sidebar.classList.contains('collapsed')) {
                        toggleSidebar();
                    }
                });
            }
            
            // Ajustar el sidebar según el tamaño de la ventana
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.add('collapsed');
                        mainContent.classList.add('expanded');
                    }
                }, 250);
            });

            // Cerrar modal al hacer clic fuera
            window.addEventListener('click', function(e) {
                const modal = document.getElementById('detallesModal');
                if (e.target === modal) {
                    cerrarModal();
                }
            });
        });
    </script>
</body>
</html>
