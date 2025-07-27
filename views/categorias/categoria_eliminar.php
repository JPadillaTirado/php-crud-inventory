<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: categoria_listar.php?mensaje=Error de conexión a la base de datos&tipo=error');
    exit;
}

// Verificar que se recibió un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: categoria_listar.php?mensaje=ID de categoría no válido&tipo=error');
    exit;
}

$categoria_id = intval($_GET['id']);

// Consultar datos de la categoría para mostrar información
$sql = "SELECT * FROM categoria WHERE categoria_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $categoria_id);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar si la categoría existe
if ($resultado->num_rows === 0) {
    header('Location: categoria_listar.php?mensaje=La categoría no existe&tipo=error');
    exit;
}

$categoria = $resultado->fetch_assoc();

// Verificar si tiene productos asociados
$sql_productos = "SELECT COUNT(*) as total FROM producto WHERE categoria_id = ?";
$stmt_productos = $conexion->prepare($sql_productos);
$stmt_productos->bind_param("i", $categoria_id);
$stmt_productos->execute();
$resultado_productos = $stmt_productos->get_result();
$total_productos = $resultado_productos->fetch_assoc()['total'];

// Verificar si ya se confirmó la eliminación
if (isset($_GET['confirmar']) && $_GET['confirmar'] == '1') {
    
    // Verificar si hay productos asociados
    if ($total_productos > 0) {
        header('Location: categoria_listar.php?mensaje=No se puede eliminar la categoría porque tiene productos asociados&tipo=error');
        exit;
    }
    
    // Eliminar la categoría de la base de datos
    $sql_delete = "DELETE FROM categoria WHERE categoria_id = ?";
    $stmt_delete = $conexion->prepare($sql_delete);
    $stmt_delete->bind_param("i", $categoria_id);
    
    if ($stmt_delete->execute()) {
        // Redirigir al listado con mensaje de éxito
        header('Location: categoria_listar.php?mensaje=Categoría eliminada correctamente&tipo=exito');
        exit;
    } else {
        // Redirigir con mensaje de error
        header('Location: categoria_listar.php?mensaje=Error al eliminar la categoría: ' . $conexion->error . '&tipo=error');
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
    <title>Eliminar Categoría - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de eliminar categoría */
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
        }
        
        .categorias-content {
            padding: 30px;
        }
        
        .confirmacion-card {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Estilos para la confirmación */
        .warning-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .warning-icon {
            font-size: 64px;
            color: #e74c3c;
            margin-bottom: 15px;
            display: block;
        }
        
        .warning-title {
            color: #e74c3c;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .warning-subtitle {
            color: #6c757d;
            font-size: 16px;
        }
        
        /* Detalles de la categoría */
        .category-details {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .category-details h4 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
            width: 120px;
            flex-shrink: 0;
        }
        
        .detail-value {
            color: #6c757d;
            flex: 1;
        }
        
        /* Mensaje de productos asociados */
        .products-warning {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        
        .products-warning .icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        
        .products-warning h4 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .products-warning p {
            margin: 0;
            opacity: 0.9;
        }
        
        /* Acciones de confirmación */
        .acciones-confirmacion {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background-color: #2980b9;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1f618d;
            transform: translateY(-2px);
        }
        
        /* Advertencia adicional */
        .advertencia-adicional {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            color: #856404;
        }
        
        .advertencia-adicional p {
            margin: 0;
            font-size: 14px;
        }
        
        /* Badge para ID */
        .category-id-badge {
            display: inline-block;
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        /* Animación de entrada */
        .confirmacion-card {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .categorias-content {
                padding: 20px;
            }
            
            .confirmacion-card {
                padding: 20px;
            }
            
            .acciones-confirmacion {
                flex-direction: column;
            }
            
            .acciones-confirmacion .btn {
                width: 100%;
                justify-content: center;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .detail-label {
                width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar con estructura mejorada -->
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
                <a href="../proveedores/proveedor_listar.php" class="nav-item">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="../compras/compra_listar.php" class="nav-item">
                    <span class="nav-icon">🛒</span>
                    <span class="nav-text">Compras</span>
                </a>
                <a href="categoria_listar.php" class="nav-item active" title="Categorías">
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
                <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">
                    <span style="margin-right: 10px;">🗑️</span>Eliminar Categoría
                    <span class="category-id-badge">ID: <?php echo $categoria_id; ?></span>
                </h1>
                <p style="color: #6c757d; font-size: 16px;">Confirmación de eliminación de categoría</p>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <div class="confirmacion-card">
                    <?php if ($total_productos > 0): ?>
                        <!-- Caso: No se puede eliminar porque tiene productos -->
                        <div class="warning-header">
                            <span class="warning-icon">⚠️</span>
                            <h2 class="warning-title">No se puede eliminar</h2>
                            <p class="warning-subtitle">Esta categoría tiene productos asociados</p>
                        </div>
                        
                        <div class="products-warning">
                            <span class="icon">📦</span>
                            <h4>Productos Asociados Encontrados</h4>
                            <p>Esta categoría tiene <strong><?php echo $total_productos; ?> producto(s)</strong> asociado(s).</p>
                            <p>Debe reasignar o eliminar estos productos antes de poder eliminar la categoría.</p>
                        </div>
                        
                        <div class="category-details">
                            <h4>📁 Detalles de la Categoría</h4>
                            <div class="detail-row">
                                <span class="detail-label">ID:</span>
                                <span class="detail-value"><?php echo $categoria['categoria_id']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Nombre:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($categoria['categoria_nombre']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Ubicación:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($categoria['categoria_ubicacion']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Productos:</span>
                                <span class="detail-value"><?php echo $total_productos; ?> productos asociados</span>
                            </div>
                        </div>
                        
                        <div class="acciones-confirmacion">
                            <a href="categoria_listar.php" class="btn btn-secondary">
                                <span>←</span> Volver al Listado
                            </a>
                            <a href="../productos/producto_listar.php" class="btn btn-primary">
                                <span>📦</span> Ver Productos
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Caso: Se puede eliminar -->
                        <div class="warning-header">
                            <span class="warning-icon">❗</span>
                            <h2 class="warning-title">Confirmar Eliminación</h2>
                            <p class="warning-subtitle">¿Está seguro que desea eliminar esta categoría?</p>
                        </div>
                        
                        <div class="category-details">
                            <h4>📁 Categoría a Eliminar</h4>
                            <div class="detail-row">
                                <span class="detail-label">ID:</span>
                                <span class="detail-value"><?php echo $categoria['categoria_id']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Nombre:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($categoria['categoria_nombre']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Ubicación:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($categoria['categoria_ubicacion']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value" style="color: #28a745;">✓ Sin productos asociados</span>
                            </div>
                        </div>
                        
                        <div class="acciones-confirmacion">
                            <a href="categoria_listar.php" class="btn btn-secondary">
                                <span>←</span> Cancelar
                            </a>
                            <a href="categoria_eliminar.php?id=<?php echo $categoria_id; ?>&confirmar=1" 
                               class="btn btn-danger" 
                               onclick="return confirm('¿Está completamente seguro? Esta acción no se puede deshacer.');">
                                <span>🗑️</span> Sí, Eliminar
                            </a>
                        </div>
                        
                        <div class="advertencia-adicional">
                            <p><strong>⚠️ Advertencia:</strong> Esta acción no se puede deshacer. La categoría será eliminada permanentemente.</p>
                        </div>
                    <?php endif; ?>
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

        // Efectos de entrada suaves
        window.addEventListener('load', function() {
            const card = document.querySelector('.confirmacion-card');
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            }
        });

        // Confirmación adicional para eliminación
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-danger') || e.target.closest('.btn-danger')) {
                e.preventDefault();
                
                const confirmMessage = "⚠️ CONFIRMACIÓN FINAL\n\n" +
                                     "¿Está completamente seguro de que desea eliminar esta categoría?\n\n" +
                                     "• Esta acción NO se puede deshacer\n" +
                                     "• La categoría será eliminada permanentemente\n" +
                                     "• Ya se verificó que no tiene productos asociados\n\n" +
                                     "Escriba 'ELIMINAR' para confirmar:";
                
                const userInput = prompt(confirmMessage);
                
                if (userInput === 'ELIMINAR') {
                    const link = e.target.closest('.btn-danger');
                    window.location.href = link.href;
                } else if (userInput !== null) {
                    alert('❌ Confirmación incorrecta. La categoría NO ha sido eliminada.');
                }
                
                return false;
            }
        });

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Escape para cancelar
            if (e.key === 'Escape') {
                window.location.href = 'categoria_listar.php';
            }
        });
    </script>
</body>
</html>
<?php
}
?>