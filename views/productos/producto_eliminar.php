<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: producto_listar.php?mensaje=Error de conexión a la base de datos&tipo=error');
    exit;
}

// Verificar que se recibió un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: producto_listar.php?mensaje=ID de producto no válido&tipo=error');
    exit;
}

$producto_id = intval($_GET['id']);

// Consultar datos del producto para mostrar información y obtener la foto
$sql = "SELECT p.*, c.categoria_nombre, u.usuario_nombre, u.usuario_apellido 
        FROM producto p
        JOIN categoria c ON p.categoria_id = c.categoria_id
        JOIN usuario u ON p.usuario_id = u.usuario_id
        WHERE p.producto_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar si el producto existe
if ($resultado->num_rows === 0) {
    header('Location: producto_listar.php?mensaje=El producto no existe&tipo=error');
    exit;
}

$producto = $resultado->fetch_assoc();

// Verificar si ya se confirmó la eliminación
if (isset($_GET['confirmar']) && $_GET['confirmar'] == '1') {
    
    // Almacenar la ruta de la foto antes de eliminar
    $foto = $producto['producto_foto'];
    
    // Eliminar el producto de la base de datos
    $sql_delete = "DELETE FROM producto WHERE producto_id = ?";
    $stmt_delete = $conexion->prepare($sql_delete);
    $stmt_delete->bind_param("i", $producto_id);
    
    if ($stmt_delete->execute()) {
        // Eliminar la imagen asociada si existe
        if (!empty($foto) && file_exists('../../uploads/' . $foto)) {
            unlink('../../uploads/' . $foto);
        }
        
        // Redirigir al listado con mensaje de éxito
        header('Location: producto_listar.php?mensaje=Producto eliminado correctamente&tipo=exito');
        exit;
    } else {
        // Redirigir con mensaje de error
        header('Location: producto_listar.php?mensaje=Error al eliminar el producto: ' . $conexion->error . '&tipo=error');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Eliminar Producto - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de eliminación */
        .dashboard-container {
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        
        .main-content {
            padding-top: 0;
        }
        
        .productos-header {
            background-color: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .productos-content {
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
        
        /* Icono de advertencia */
        .warning-icon {
            font-size: 72px;
            text-align: center;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        /* Detalles del producto */
        .detalles-producto {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .detalle-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detalle-item:last-child {
            border-bottom: none;
        }
        
        .detalle-label {
            font-weight: 600;
            color: #495057;
        }
        
        .detalle-valor {
            color: #212529;
        }
        
        /* Imagen del producto */
        .producto-imagen-preview {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        
        .producto-imagen-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Mensaje de confirmación */
        .confirmacion-mensaje {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .confirmacion-mensaje h2 {
            color: #e74c3c;
            margin-bottom: 10px;
        }
        
        .confirmacion-mensaje p {
            color: #6c757d;
            font-size: 16px;
        }
        
        /* Acciones */
        .acciones-confirmacion {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        /* Advertencia adicional */
        .advertencia-adicional {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            text-align: center;
        }
        
        .advertencia-adicional p {
            margin: 0;
            font-size: 14px;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .breadcrumb a {
            color: #2980b9;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .productos-content {
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
                <a href="producto_listar.php" class="nav-item active" title="Productos">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Productos</span>
                </a>
                <a href="../../facturas_listar.php" class="nav-item" title="Facturación">
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
                <a href="../../en_construccion.php?modulo=proveedores" class="nav-item" title="Proveedores">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="../../en_construccion.php?modulo=pedidos" class="nav-item" title="Pedidos">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Pedidos</span>
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
            <div class="productos-header">
                <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">Eliminar Producto</h1>
                <p style="color: #6c757d; font-size: 16px;">Confirma la eliminación del producto</p>
            </div>

            <!-- Contenido principal -->
            <div class="productos-content">
                <div class="confirmacion-card">
                    <div class="warning-icon">⚠️</div>
                    
                    <div class="confirmacion-mensaje">
                        <h2>¿Está seguro que desea eliminar este producto?</h2>
                        <p>Esta acción no se puede deshacer</p>
                    </div>
                    
                    <div class="detalles-producto">
                        <div class="detalle-item">
                            <span class="detalle-label">ID:</span>
                            <span class="detalle-valor"><?php echo $producto['producto_id']; ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Código:</span>
                            <span class="detalle-valor"><?php echo htmlspecialchars($producto['producto_codigo']); ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Nombre:</span>
                            <span class="detalle-valor"><?php echo htmlspecialchars($producto['producto_nombre']); ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Precio:</span>
                            <span class="detalle-valor">$<?php echo number_format($producto['producto_precio'], 2); ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Stock:</span>
                            <span class="detalle-valor"><?php echo $producto['producto_stock']; ?> unidades</span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Categoría:</span>
                            <span class="detalle-valor"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Usuario responsable:</span>
                            <span class="detalle-valor"><?php echo htmlspecialchars($producto['usuario_nombre'] . ' ' . $producto['usuario_apellido']); ?></span>
                        </div>
                        
                        <?php 
                        if (!empty($producto['producto_foto']) && file_exists('../../uploads/' . $producto['producto_foto'])): 
                        ?>
                            <div class="producto-imagen-preview">
                                <p style="margin-bottom: 10px; font-weight: 600;">Imagen del producto:</p>
                                <img src="../../uploads/<?php echo $producto['producto_foto']; ?>" 
                                     alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>">
                                <p style="margin-top: 10px; color: #6c757d; font-size: 14px;">
                                    Esta imagen también será eliminada
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="acciones-confirmacion">
                        <a href="producto_listar.php" class="btn btn-secondary">
                            <span style="margin-right: 5px;">←</span> Cancelar
                        </a>
                        <a href="producto_eliminar.php?id=<?php echo $producto_id; ?>&confirmar=1" 
                           class="btn btn-danger"
                           onclick="return confirm('¿Está ABSOLUTAMENTE seguro? Esta acción NO se puede deshacer.');">
                            <span style="margin-right: 5px;">🗑️</span> Sí, Eliminar Permanentemente
                        </a>
                    </div>
                    
                    <div class="advertencia-adicional">
                        <p><strong>⚠️ Advertencia:</strong> Al eliminar este producto, se perderá toda la información asociada incluyendo la imagen del producto.</p>
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