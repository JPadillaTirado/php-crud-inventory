<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: compra_listar.php?mensaje=Error de conexión a la base de datos&tipo=error');
    exit;
}

// Verificar que se recibió un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: compra_listar.php?mensaje=ID de compra no válido&tipo=error');
    exit;
}

$compra_id = intval($_GET['id']);

// Consultar datos de la compra
$sql_compra = "SELECT 
                c.*,
                p.proveedor_nombre,
                p.proveedor_rut_nit,
                u.usuario_nombre,
                u.usuario_apellido
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

// Verificar que la compra esté en estado pendiente
if ($compra['compra_estado'] !== 'pendiente') {
    $mensaje = 'Esta compra ya fue procesada anteriormente';
    if ($compra['compra_estado'] === 'recibida') {
        $mensaje = 'Esta compra ya fue marcada como recibida';
    } elseif ($compra['compra_estado'] === 'cancelada') {
        $mensaje = 'Esta compra está cancelada y no se puede procesar';
    }
    header("Location: compra_ver.php?id=$compra_id&mensaje=$mensaje&tipo=error");
    exit;
}

// Consultar productos de la compra
$sql_productos = "SELECT 
                    dc.*,
                    pr.producto_nombre,
                    pr.producto_codigo,
                    pr.producto_stock as stock_actual
                FROM detalle_compra dc
                INNER JOIN producto pr ON dc.producto_id = pr.producto_id
                WHERE dc.compra_id = ?
                ORDER BY pr.producto_nombre";

$stmt_productos = $conexion->prepare($sql_productos);
$stmt_productos->bind_param("i", $compra_id);
$stmt_productos->execute();
$resultado_productos = $stmt_productos->get_result();

$productos = array();
$total_productos = 0;
$total_unidades = 0;

while ($producto = $resultado_productos->fetch_assoc()) {
    $productos[] = $producto;
    $total_productos++;
    $total_unidades += $producto['detalle_cantidad'];
}

// Verificar si se confirmó la recepción
if (isset($_GET['confirmar']) && $_GET['confirmar'] == '1') {
    
    // Iniciar transacción
    $conexion->begin_transaction();
    
    try {
        // 1. Actualizar el estado de la compra
        $sql_update_compra = "UPDATE compra SET compra_estado = 'recibida' WHERE compra_id = ?";
        $stmt_update = $conexion->prepare($sql_update_compra);
        $stmt_update->bind_param("i", $compra_id);
        
        if (!$stmt_update->execute()) {
            throw new Exception("Error al actualizar el estado de la compra");
        }
        
        // 2. Actualizar el stock de cada producto
        foreach ($productos as $producto) {
            $sql_update_stock = "UPDATE producto 
                               SET producto_stock = producto_stock + ? 
                               WHERE producto_id = ?";
            $stmt_stock = $conexion->prepare($sql_update_stock);
            $stmt_stock->bind_param("ii", $producto['detalle_cantidad'], $producto['producto_id']);
            
            if (!$stmt_stock->execute()) {
                throw new Exception("Error al actualizar el stock del producto: " . $producto['producto_nombre']);
            }
        }
        
        // Confirmar transacción
        $conexion->commit();
        
        // Redirigir con mensaje de éxito
        header("Location: compra_ver.php?id=$compra_id&mensaje=Compra marcada como recibida y stock actualizado correctamente&tipo=exito");
        exit;
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conexion->rollback();
        header("Location: compra_recibir.php?id=$compra_id&error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Mostrar página de confirmación
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Recibir Compra <?php echo htmlspecialchars($compra['compra_codigo']); ?> - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de recibir compra */
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
        
        /* Tarjeta de confirmación */
        .confirmacion-card {
            background-color: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            max-width: 700px;
            margin: 0 auto;
            text-align: center;
        }
        
        .success-icon {
            font-size: 72px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        .confirmacion-mensaje h2 {
            color: #27ae60;
            margin-bottom: 15px;
            font-size: 24px;
            font-weight: 600;
        }
        
        .confirmacion-mensaje p {
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        /* Información de la compra */
        .compra-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        
        .compra-info h3 {
            margin: 0 0 15px 0;
            font-size: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            padding-bottom: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .info-value {
            font-size: 20px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Productos a recibir */
        .productos-preview {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .productos-preview h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
        }
        
        .producto-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .producto-item:last-child {
            border-bottom: none;
        }
        
        .producto-nombre {
            flex: 1;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .producto-cantidad {
            color: #27ae60;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .stock-info {
            font-size: 12px;
            color: #6c757d;
        }
        
        /* Alerta de cambios */
        .cambios-alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .cambios-alert h4 {
            margin: 0 0 10px 0;
            color: #6c5116;
        }
        
        /* Botones de acción */
        .acciones-confirmacion {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background-color: #27ae60;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .btn-success:hover {
            background-color: #229954;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(39, 174, 96, 0.3);
        }
        
        .btn-success::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-success:hover::before {
            left: 100%;
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
            
            .confirmacion-card {
                margin: 0;
                border-radius: 0;
                padding: 30px 20px;
            }
            
            .acciones-confirmacion {
                flex-direction: column;
            }
            
            .btn {
                text-align: center;
                justify-content: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .producto-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
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
                        📦 Recibir Compra
                    </h1>
                    <p style="color: #6c757d; font-size: 16px;">Confirma la recepción de la mercancía</p>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mensaje error" style="max-width: 700px; margin: 0 auto 20px;">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="confirmacion-card">
                    <div class="success-icon">📦</div>
                    
                    <div class="confirmacion-mensaje">
                        <h2>¿Confirmar recepción de la compra?</h2>
                        <p>Estás a punto de marcar esta compra como <strong>recibida</strong>. 
                           Esta acción actualizará automáticamente el inventario y no se puede deshacer.</p>
                    </div>
                    
                    <!-- Información de la compra -->
                    <div class="compra-info">
                        <h3>📋 Información de la Compra</h3>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span><strong>Código:</strong> <?php echo htmlspecialchars($compra['compra_codigo']); ?></span>
                            <span><strong>Proveedor:</strong> <?php echo htmlspecialchars($compra['proveedor_nombre']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span><strong>Fecha:</strong> 
                                <?php 
                                $fecha = new DateTime($compra['compra_fecha']);
                                echo $fecha->format('d/m/Y');
                                ?>
                            </span>
                            <span><strong>Total:</strong> $<?php echo number_format($compra['compra_total'], 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-value"><?php echo $total_productos; ?></span>
                                <span class="info-label">Tipos de productos</span>
                            </div>
                            <div class="info-item">
                                <span class="info-value"><?php echo number_format($total_unidades); ?></span>
                                <span class="info-label">Unidades totales</span>
                            </div>
                            <div class="info-item">
                                <span class="info-value"><?php echo htmlspecialchars($compra['usuario_nombre'] . ' ' . $compra['usuario_apellido']); ?></span>
                                <span class="info-label">Registrado por</span>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de productos -->
                    <div class="productos-preview">
                        <h4>📦 Productos a recibir en inventario:</h4>
                        <?php foreach ($productos as $producto): ?>
                            <div class="producto-item">
                                <div class="producto-nombre">
                                    <?php echo htmlspecialchars($producto['producto_nombre']); ?>
                                    <div class="stock-info">
                                        Código: <?php echo htmlspecialchars($producto['producto_codigo']); ?> | 
                                        Stock actual: <?php echo number_format($producto['stock_actual']); ?>
                                    </div>
                                </div>
                                <div class="producto-cantidad">
                                    +<?php echo number_format($producto['detalle_cantidad']); ?> unidades
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Alerta de cambios -->
                    <div class="cambios-alert">
                        <h4>⚠️ Cambios que se realizarán:</h4>
                        <ul style="text-align: left; margin: 10px 0;">
                            <li>El estado de la compra cambiará a <strong>"Recibida"</strong></li>
                            <li>Se actualizará automáticamente el stock de <strong><?php echo $total_productos; ?> producto(s)</strong></li>
                            <li>Se agregarán <strong><?php echo number_format($total_unidades); ?> unidades</strong> al inventario total</li>
                            <li>Esta acción <strong>NO se puede deshacer</strong></li>
                        </ul>
                    </div>

                    <!-- Botones de acción -->
                    <div class="acciones-confirmacion">
                        <a href="compra_ver.php?id=<?php echo $compra_id; ?>" class="btn btn-secondary">
                            <span style="margin-right: 8px;">↩️</span> Cancelar
                        </a>
                        <a href="compra_recibir.php?id=<?php echo $compra_id; ?>&confirmar=1" 
                           class="btn btn-success" 
                           onclick="return confirmarRecepcion();">
                            <span style="margin-right: 8px;">✅</span> Confirmar Recepción
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

        // Función de confirmación final
        function confirmarRecepcion() {
            return confirm(
                '🚨 CONFIRMACIÓN FINAL\n\n' +
                'Esta acción marcará la compra como RECIBIDA y actualizará el inventario.\n\n' +
                '¿Está seguro de que desea continuar?\n\n' +
                'Esta acción NO se puede deshacer.'
            );
        }

        // Animación adicional al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.confirmacion-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>
<?php
}
?>