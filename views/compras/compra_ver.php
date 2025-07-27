<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}

// Verificar que se recibió un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: compra_listar.php?mensaje=ID de compra no válido&tipo=error');
    exit;
}

$compra_id = intval($_GET['id']);

// Consultar datos completos de la compra
$sql_compra = "SELECT 
                c.*,
                p.proveedor_nombre,
                p.proveedor_rut_nit,
                p.proveedor_telefono,
                p.proveedor_email,
                p.proveedor_direccion,
                p.proveedor_ciudad,
                p.proveedor_contacto,
                u.usuario_nombre,
                u.usuario_apellido,
                u.usuario_email
            FROM compra c
            INNER JOIN proveedor p ON c.proveedor_id = p.proveedor_id
            INNER JOIN usuario u ON c.usuario_id = u.usuario_id
            WHERE c.compra_id = ?";

$stmt_compra = $conexion->prepare($sql_compra);
$stmt_compra->bind_param("i", $compra_id);
$stmt_compra->execute();
$resultado_compra = $stmt_compra->get_result();

// Verificar si la compra existe
if ($resultado_compra->num_rows === 0) {
    header('Location: compra_listar.php?mensaje=La compra no existe&tipo=error');
    exit;
}

$compra = $resultado_compra->fetch_assoc();

// Consultar detalles de la compra (productos)
$sql_detalles = "SELECT 
                    dc.*,
                    pr.producto_nombre,
                    pr.producto_codigo,
                    pr.producto_foto,
                    cat.categoria_nombre
                FROM detalle_compra dc
                INNER JOIN producto pr ON dc.producto_id = pr.producto_id
                INNER JOIN categoria cat ON pr.categoria_id = cat.categoria_id
                WHERE dc.compra_id = ?
                ORDER BY pr.producto_nombre";

$stmt_detalles = $conexion->prepare($sql_detalles);
$stmt_detalles->bind_param("i", $compra_id);
$stmt_detalles->execute();
$resultado_detalles = $stmt_detalles->get_result();

// Calcular estadísticas
$total_productos = 0;
$total_cantidad = 0;
$productos_detalles = array();

while ($detalle = $resultado_detalles->fetch_assoc()) {
    $productos_detalles[] = $detalle;
    $total_productos++;
    $total_cantidad += $detalle['detalle_cantidad'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Ver Compra <?php echo htmlspecialchars($compra['compra_codigo']); ?> - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de ver compra */
        .dashboard-container {
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        
        .main-content {
            padding-top: 0;
        }
        
        .categorias-header {
            background-color: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .categorias-content {
            padding: 30px;
        }
        
        /* Ajuste para los mensajes */
        .mensaje {
            margin-bottom: 20px;
        }
        
        /* Tarjetas de información */
        .info-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .info-card h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 18px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        
        /* Estados de compras */
        .estado-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .estado-pendiente {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .estado-recibida {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .estado-cancelada {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Información en filas */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            flex: 0 0 150px;
        }
        
        .info-value {
            color: #6c757d;
            flex: 1;
        }
        
        /* Tabla de productos */
        .productos-table {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .productos-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .productos-table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        .productos-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .producto-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        
        .producto-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .producto-details h5 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .producto-details small {
            color: #6c757d;
            font-size: 13px;
        }
        
        /* Totales */
        .totales-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .totales-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .total-item {
            text-align: center;
        }
        
        .total-value {
            font-size: 24px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        
        .total-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Acciones */
        .acciones-container {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .acciones-grid {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }
        
        .btn-warning {
            background-color: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #e67e22;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background-color: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #229954;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        /* Formato de moneda */
        .monto {
            font-weight: 600;
            color: #27ae60;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .categorias-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .categorias-content {
                padding: 20px;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .info-label {
                flex: none;
            }
            
            .productos-table {
                overflow-x: auto;
            }
            
            .acciones-grid {
                flex-direction: column;
            }
            
            .totales-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-small {
                padding: 4px 8px;
                font-size: 12px;
            }
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
                <a href="../../dashboard.php" class="nav-item" title="Dashboard">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="../productos/producto_listar.php" class="nav-item" title="Productos">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Productos</span>
                </a>
                <a href="../../facturas_listar.php" class="nav-item">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-text">Facturación</span>
                </a>
                <a href="../../en_construccion.php?modulo=configuraciones" class="nav-item" title="Configuraciones">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Configuraciones</span>
                </a>
                <a href="../usuarios/usuario_listar.php" class="nav-item" title="Usuarios">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Usuarios</span>
                </a>
                <a href="../proveedores/proveedor_listar.php" class="nav-item" title="Proveedores">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="compra_listar.php" class="nav-item active" title="Compras">
                    <span class="nav-icon">🛒</span>
                    <span class="nav-text">Compras</span>
                </a>
                <a href="../categorias/categoria_listar.php" class="nav-item" title="Categorías">
                    <span class="nav-icon">📁</span>
                    <span class="nav-text">Categoría</span>
                </a>
                <a href="../../en_construccion.php?modulo=informes" class="nav-item" title="Informes">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Informes</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Header de la página -->
            <div class="categorias-header">
                <div>
                    <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">
                        🛒 Compra <?php echo htmlspecialchars($compra['compra_codigo']); ?>
                    </h1>
                    <p style="color: #6c757d; font-size: 16px;">Detalles completos de la orden de compra</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <?php
                    $estado_class = '';
                    $estado_icon = '';
                    $estado_text = '';
                    
                    switch($compra['compra_estado']) {
                        case 'pendiente':
                            $estado_class = 'estado-pendiente';
                            $estado_icon = '⏳';
                            $estado_text = 'Pendiente';
                            break;
                        case 'recibida':
                            $estado_class = 'estado-recibida';
                            $estado_icon = '✅';
                            $estado_text = 'Recibida';
                            break;
                        case 'cancelada':
                            $estado_class = 'estado-cancelada';
                            $estado_icon = '❌';
                            $estado_text = 'Cancelada';
                            break;
                    }
                    ?>
                    <span class="estado-badge <?php echo $estado_class; ?>">
                        <?php echo $estado_icon . ' ' . $estado_text; ?>
                    </span>
                    <a href="compra_listar.php" class="btn btn-secondary">
                        <span style="margin-right: 5px;">←</span> Volver
                    </a>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="mensaje <?php echo $_GET['tipo'] ?? 'info'; ?>">
                        <?php echo htmlspecialchars($_GET['mensaje']); ?>
                    </div>
                <?php endif; ?>

                <!-- Información General -->
                <div class="info-card">
                    <h3>📋 Información General</h3>
                    <div class="info-row">
                        <span class="info-label">ID Compra:</span>
                        <span class="info-value"><?php echo $compra['compra_id']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Código:</span>
                        <span class="info-value"><strong><?php echo htmlspecialchars($compra['compra_codigo']); ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha Compra:</span>
                        <span class="info-value">
                            <?php 
                            $fecha = new DateTime($compra['compra_fecha']);
                            echo $fecha->format('d/m/Y');
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Registrado por:</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($compra['usuario_nombre'] . ' ' . $compra['usuario_apellido']); ?>
                            <small style="color: #6c757d;">
                                (<?php echo htmlspecialchars($compra['usuario_email']); ?>)
                            </small>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha Registro:</span>
                        <span class="info-value">
                            <?php 
                            $fecha_registro = new DateTime($compra['compra_fecha_registro']);
                            echo $fecha_registro->format('d/m/Y H:i:s');
                            ?>
                        </span>
                    </div>
                    <?php if (!empty($compra['compra_observaciones'])): ?>
                    <div class="info-row">
                        <span class="info-label">Observaciones:</span>
                        <span class="info-value"><?php echo nl2br(htmlspecialchars($compra['compra_observaciones'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Información del Proveedor -->
                <div class="info-card">
                    <h3>🏢 Información del Proveedor</h3>
                    <div class="info-row">
                        <span class="info-label">Empresa:</span>
                        <span class="info-value"><strong><?php echo htmlspecialchars($compra['proveedor_nombre']); ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">RUT/NIT:</span>
                        <span class="info-value"><?php echo htmlspecialchars($compra['proveedor_rut_nit']); ?></span>
                    </div>
                    <?php if (!empty($compra['proveedor_contacto'])): ?>
                    <div class="info-row">
                        <span class="info-label">Contacto:</span>
                        <span class="info-value"><?php echo htmlspecialchars($compra['proveedor_contacto']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($compra['proveedor_telefono'])): ?>
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value">📞 <?php echo htmlspecialchars($compra['proveedor_telefono']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($compra['proveedor_email'])): ?>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">✉️ <?php echo htmlspecialchars($compra['proveedor_email']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($compra['proveedor_direccion'])): ?>
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        <span class="info-value">
                            📍 <?php echo htmlspecialchars($compra['proveedor_direccion']); ?>
                            <?php if (!empty($compra['proveedor_ciudad'])): ?>
                                - <?php echo htmlspecialchars($compra['proveedor_ciudad']); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Productos de la Compra -->
                <div class="productos-table">
                    <h3 style="margin: 0 0 20px 0; color: #2c3e50;">📦 Productos Comprados</h3>
                    
                    <?php if (!empty($productos_detalles)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_detalles as $detalle): ?>
                                    <tr>
                                        <td>
                                            <div class="producto-info">
                                                <?php if (!empty($detalle['producto_foto'])): ?>
                                                    <img src="../../uploads/<?php echo $detalle['producto_foto']; ?>" 
                                                         alt="<?php echo htmlspecialchars($detalle['producto_nombre']); ?>"
                                                         class="producto-img">
                                                <?php else: ?>
                                                    <div class="producto-img" style="background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #6c757d;">📦</div>
                                                <?php endif; ?>
                                                <div class="producto-details">
                                                    <h5><?php echo htmlspecialchars($detalle['producto_nombre']); ?></h5>
                                                    <small>Código: <?php echo htmlspecialchars($detalle['producto_codigo']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="background-color: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                                <?php echo htmlspecialchars($detalle['categoria_nombre']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong style="color: #2c3e50;"><?php echo number_format($detalle['detalle_cantidad']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="monto">$<?php echo number_format($detalle['detalle_precio_compra'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td>
                                            <span class="monto"><strong>$<?php echo number_format($detalle['detalle_subtotal'], 0, ',', '.'); ?></strong></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #6c757d;">
                            <p>No hay productos registrados en esta compra.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Totales -->
                <div class="totales-card">
                    <h3 style="margin: 0 0 20px 0; text-align: center;">💰 Resumen de Totales</h3>
                    <div class="totales-grid">
                        <div class="total-item">
                            <span class="total-value"><?php echo $total_productos; ?></span>
                            <span class="total-label">Tipos de Productos</span>
                        </div>
                        <div class="total-item">
                            <span class="total-value"><?php echo number_format($total_cantidad); ?></span>
                            <span class="total-label">Cantidad Total</span>
                        </div>
                        <div class="total-item">
                            <span class="total-value">$<?php echo number_format($compra['compra_total'], 0, ',', '.'); ?></span>
                            <span class="total-label">Total de la Compra</span>
                        </div>
                    </div>
                </div>

                <!-- Acciones Disponibles -->
                <div class="acciones-container">
                    <h3 style="margin: 0 0 20px 0; color: #2c3e50;">⚡ Acciones Disponibles</h3>
                    <div class="acciones-grid">
                        <a href="compra_listar.php" class="btn btn-secondary">
                            <span style="margin-right: 8px;">📋</span> Ver Todas las Compras
                        </a>
                        
                        <?php if ($compra['compra_estado'] == 'pendiente'): ?>
                            <a href="compra_editar.php?id=<?php echo $compra['compra_id']; ?>" class="btn btn-warning">
                                <span style="margin-right: 8px;">✏️</span> Editar Compra
                            </a>
                            <a href="compra_recibir.php?id=<?php echo $compra['compra_id']; ?>" class="btn btn-success">
                                <span style="margin-right: 8px;">📦</span> Marcar como Recibida
                            </a>
                        <?php elseif ($compra['compra_estado'] == 'recibida'): ?>
                            <button class="btn" style="background-color: #d4edda; color: #155724; cursor: default;">
                                <span style="margin-right: 8px;">✅</span> Compra Completada
                            </button>
                        <?php else: ?>
                            <button class="btn" style="background-color: #f8d7da; color: #721c24; cursor: default;">
                                <span style="margin-right: 8px;">❌</span> Compra Cancelada
                            </button>
                        <?php endif; ?>
                        
                        <a href="compra_nuevo.php" class="btn btn-primary">
                            <span style="margin-right: 8px;">+</span> Nueva Compra
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Script mejorado para el manejo del sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mainContent = document.querySelector('.main-content');
            
            // Función para alternar el sidebar
            function toggleSidebar() {
                sidebar.classList.toggle('collapsed');
                
                // Actualizar la clase del contenido principal
                if (sidebar.classList.contains('collapsed')) {
                    mainContent.classList.add('expanded');
                } else {
                    mainContent.classList.remove('expanded');
                }
                
                // Guardar el estado en localStorage
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
                
                // Disparar un evento personalizado
                window.dispatchEvent(new Event('sidebarToggled'));
            }
            
            // Event listener para el botón de toggle
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            
            // Restaurar el estado del sidebar desde localStorage
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
            
            // Manejo del sidebar en dispositivos móviles
            let touchStartX = 0;
            let touchEndX = 0;
            
            // Detectar swipe en móviles
            document.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            });
            
            document.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            });
            
            function handleSwipe() {
                // Swipe derecha para abrir
                if (touchEndX > touchStartX + 50 && sidebar.classList.contains('collapsed')) {
                    toggleSidebar();
                }
                // Swipe izquierda para cerrar
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
        });
    </script>
</body>
</html>