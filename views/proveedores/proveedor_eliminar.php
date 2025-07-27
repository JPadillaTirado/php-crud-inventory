<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: proveedor_listar.php?mensaje=Error de conexión a la base de datos&tipo=error');
    exit;
}

// Verificar que se recibió un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: proveedor_listar.php?mensaje=ID de proveedor no válido&tipo=error');
    exit;
}

$proveedor_id = intval($_GET['id']);

// Consultar datos del proveedor para mostrar información
$sql = "SELECT * FROM proveedor WHERE proveedor_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar si el proveedor existe
if ($resultado->num_rows === 0) {
    header('Location: proveedor_listar.php?mensaje=El proveedor no existe&tipo=error');
    exit;
}

$proveedor = $resultado->fetch_assoc();

// Verificar si tiene compras asociadas
$sql_compras = "SELECT COUNT(*) as total FROM compra WHERE proveedor_id = ?";
$stmt_compras = $conexion->prepare($sql_compras);
$stmt_compras->bind_param("i", $proveedor_id);
$stmt_compras->execute();
$resultado_compras = $stmt_compras->get_result();
$total_compras = $resultado_compras->fetch_assoc()['total'];

// Verificar si ya se confirmó la eliminación
if (isset($_GET['confirmar']) && $_GET['confirmar'] == '1') {
    
    // Verificar si hay compras asociadas
    if ($total_compras > 0) {
        header('Location: proveedor_listar.php?mensaje=No se puede eliminar el proveedor porque tiene compras asociadas&tipo=error');
        exit;
    }
    
    // Eliminar el proveedor de la base de datos
    $sql_delete = "DELETE FROM proveedor WHERE proveedor_id = ?";
    $stmt_delete = $conexion->prepare($sql_delete);
    $stmt_delete->bind_param("i", $proveedor_id);
    
    if ($stmt_delete->execute()) {
        // Redirigir al listado con mensaje de éxito
        header('Location: proveedor_listar.php?mensaje=Proveedor eliminado correctamente&tipo=exito');
        exit;
    } else {
        // Redirigir con mensaje de error
        header('Location: proveedor_listar.php?mensaje=Error al eliminar el proveedor: ' . $conexion->error . '&tipo=error');
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
    <title>Eliminar Proveedor - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de eliminar proveedor */
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
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }
        
        .warning-icon {
            font-size: 72px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .confirmacion-mensaje h2 {
            color: #e74c3c;
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
        
        /* Detalles del proveedor */
        .detalles-proveedor {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .detalles-proveedor h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 18px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        .detalles-proveedor .detalle-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .detalles-proveedor .detalle-label {
            font-weight: 600;
            color: #495057;
        }
        
        .detalles-proveedor .detalle-valor {
            color: #6c757d;
        }
        
        /* Mensaje de bloqueo */
        .mensaje-bloqueo {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .mensaje-bloqueo h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .mensaje-bloqueo p {
            margin: 0;
            opacity: 0.9;
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
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }
        
        /* Advertencia adicional */
        .advertencia-adicional {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            color: #856404;
        }
        
        .advertencia-adicional strong {
            color: #6c5116;
        }
        
        /* Estados del proveedor */
        .estado-activo {
            color: #28a745;
            font-weight: 600;
        }
        
        .estado-inactivo {
            color: #dc3545;
            font-weight: 600;
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
                <a href="proveedor_listar.php" class="nav-item active" title="Proveedores">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="../compras/compra_listar.php" class="nav-item">
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
                    <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">Eliminar Proveedor</h1>
                    <p style="color: #6c757d; font-size: 16px;">Confirma la eliminación del proveedor</p>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <div class="confirmacion-card">
                    <div class="warning-icon">⚠️</div>
                    
                    <?php if ($total_compras > 0): ?>
                        <!-- Mensaje si el proveedor tiene compras asociadas -->
                        <div class="mensaje-bloqueo">
                            <h3>❌ No se puede eliminar este proveedor</h3>
                            <p>Este proveedor tiene <strong><?php echo $total_compras; ?> compra(s)</strong> registrada(s) en el sistema.</p>
                            <p>Debe procesar o eliminar estas compras antes de poder eliminar el proveedor.</p>
                        </div>
                        
                        <div class="detalles-proveedor">
                            <h3>📊 Información del Proveedor</h3>
                            <div class="detalle-item">
                                <span class="detalle-label">ID:</span>
                                <span class="detalle-valor"><?php echo $proveedor['proveedor_id']; ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Nombre:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($proveedor['proveedor_nombre']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">RUT/NIT:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($proveedor['proveedor_rut_nit']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Estado:</span>
                                <span class="detalle-valor">
                                    <?php if ($proveedor['proveedor_estado'] == 1): ?>
                                        <span class="estado-activo">✓ Activo</span>
                                    <?php else: ?>
                                        <span class="estado-inactivo">○ Inactivo</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Compras registradas:</span>
                                <span class="detalle-valor"><strong><?php echo $total_compras; ?></strong></span>
                            </div>
                        </div>
                        
                        <div class="acciones-confirmacion">
                            <a href="proveedor_listar.php" class="btn btn-primary">
                                <span style="margin-right: 8px;">←</span> Volver al Listado
                            </a>
                            <a href="../../en_construccion.php?modulo=compras" class="btn btn-secondary">
                                <span style="margin-right: 8px;">📋</span> Ver Compras
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <!-- Confirmación normal si no hay compras asociadas -->
                        <div class="confirmacion-mensaje">
                            <h2>¿Está seguro que desea eliminar este proveedor?</h2>
                            <p>Esta acción eliminará permanentemente el proveedor del sistema y no se puede deshacer.</p>
                        </div>
                        
                        <div class="detalles-proveedor">
                            <h3>📋 Detalles del Proveedor a Eliminar</h3>
                            <div class="detalle-item">
                                <span class="detalle-label">ID:</span>
                                <span class="detalle-valor"><?php echo $proveedor['proveedor_id']; ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Nombre:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($proveedor['proveedor_nombre']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">RUT/NIT:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($proveedor['proveedor_rut_nit']); ?></span>
                            </div>
                            <?php if ($proveedor['proveedor_contacto']): ?>
                            <div class="detalle-item">
                                <span class="detalle-label">Contacto:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($proveedor['proveedor_contacto']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($proveedor['proveedor_telefono']): ?>
                            <div class="detalle-item">
                                <span class="detalle-label">Teléfono:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($proveedor['proveedor_telefono']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($proveedor['proveedor_email']): ?>
                            <div class="detalle-item">
                                <span class="detalle-label">Email:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($proveedor['proveedor_email']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detalle-item">
                                <span class="detalle-label">Estado:</span>
                                <span class="detalle-valor">
                                    <?php if ($proveedor['proveedor_estado'] == 1): ?>
                                        <span class="estado-activo">✓ Activo</span>
                                    <?php else: ?>
                                        <span class="estado-inactivo">○ Inactivo</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="acciones-confirmacion">
                            <a href="proveedor_listar.php" class="btn btn-secondary">
                                <span style="margin-right: 8px;">↩️</span> Cancelar
                            </a>
                            <a href="proveedor_eliminar.php?id=<?php echo $proveedor_id; ?>&confirmar=1" class="btn btn-danger">
                                <span style="margin-right: 8px;">🗑️</span> Sí, Eliminar
                            </a>
                        </div>
                        
                        <div class="advertencia-adicional">
                            <p><strong>⚠️ Advertencia:</strong> Esta acción no se puede deshacer. Una vez eliminado, toda la información del proveedor se perderá permanentemente.</p>
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

        // Confirmación adicional para la eliminación
        document.addEventListener('DOMContentLoaded', function() {
            const deleteBtn = document.querySelector('.btn-danger');
            
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function(e) {
                    if (!confirm('⚠️ CONFIRMACIÓN FINAL\n\n¿Está absolutamente seguro que desea eliminar este proveedor?\n\nEsta acción NO se puede deshacer.')) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php
}
?>