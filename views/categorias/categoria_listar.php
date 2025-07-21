<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}

// Configuración de paginación
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$categorias_por_pagina = ITEMS_POR_PAGINA;
$inicio = ($pagina_actual - 1) * $categorias_por_pagina;

// Obtener el total de categorías para la paginación
$sql_total = "SELECT COUNT(*) as total FROM categoria";
$resultado_total = $conexion->query($sql_total);
$total_categorias = $resultado_total->fetch_assoc()['total'];
$total_paginas = ceil($total_categorias / $categorias_por_pagina);

// Obtener categorías
$sql = "SELECT * FROM categoria ORDER BY categoria_nombre ASC LIMIT $inicio, $categorias_por_pagina";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Listado de Categorías - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de categorías */
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
        
        /* Ajustes para la tabla */
        .tabla-resultados {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        /* Ajuste para los mensajes */
        .mensaje {
            margin-bottom: 20px;
        }
        
        /* Badges para el total de productos */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .badge.badge-primary {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .badge.badge-success {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .badge.badge-warning {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        /* Icono de ubicación */
        .ubicacion-icon {
            color: #6c757d;
            margin-right: 5px;
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
            .categorias-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .categorias-content {
                padding: 20px;
            }
            
            table {
                font-size: 14px;
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
                <a href="../../en_construccion.php?modulo=proveedores" class="nav-item" title="Proveedores">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="../../en_construccion.php?modulo=Compras" class="nav-item" title="Compras">
                    <span class="nav-icon">📋</span>
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
                <div>
                    <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">Listado de Categorías</h1>
                    <p style="color: #6c757d; font-size: 16px;">Administra las categorías de productos</p>
                </div>
                <a href="categoria_nuevo.php" class="btn btn-primary">
                    <span style="margin-right: 5px;">+</span> Agregar Nueva Categoría
                </a>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="mensaje <?php echo $_GET['tipo'] ?? 'info'; ?>">
                        <?php echo htmlspecialchars($_GET['mensaje']); ?>
                    </div>
                <?php endif; ?>

                <section class="tabla-resultados">
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <!-- Información de resumen -->
                        <div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px;">
                            <p style="margin: 0; color: #495057;">
                                Mostrando <strong><?php echo $inicio + 1; ?></strong> - 
                                <strong><?php echo min($inicio + $categorias_por_pagina, $total_categorias); ?></strong> 
                                de <strong><?php echo $total_categorias; ?></strong> categorías
                            </p>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Ubicación</th>
                                    <th>Total Productos</th>
                                    <th>Estado</th>
                                    <th style="text-align: right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($categoria = $resultado->fetch_assoc()): 
                                    // Consultar cantidad de productos en esta categoría
                                    $sql_productos = "SELECT COUNT(*) as total FROM producto WHERE categoria_id = " . $categoria['categoria_id'];
                                    $resultado_productos = $conexion->query($sql_productos);
                                    $total_productos = $resultado_productos ? $resultado_productos->fetch_assoc()['total'] : 0;
                                    
                                    // Determinar el badge según la cantidad de productos
                                    $badge_class = 'badge-primary';
                                    if ($total_productos == 0) {
                                        $badge_class = 'badge-warning';
                                    } elseif ($total_productos > 10) {
                                        $badge_class = 'badge-success';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $categoria['categoria_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($categoria['categoria_nombre']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="ubicacion-icon">📍</span>
                                            <?php echo htmlspecialchars($categoria['categoria_ubicacion']); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $total_productos; ?> productos
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($total_productos > 0): ?>
                                                <span style="color: #28a745;">✓ Activa</span>
                                            <?php else: ?>
                                                <span style="color: #6c757d;">○ Sin productos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="acciones" style="text-align: right;">
                                            <a href="categoria_editar.php?id=<?php echo $categoria['categoria_id']; ?>" 
                                               class="btn-small" title="Editar categoría">
                                                <span style="margin-right: 5px;">✏️</span> Editar
                                            </a>
                                            <a href="categoria_eliminar.php?id=<?php echo $categoria['categoria_id']; ?>" 
                                               class="btn-small btn-danger" title="Eliminar categoría"
                                               onclick="return confirm('¿Está seguro que desea eliminar esta categoría?');">
                                                <span style="margin-right: 5px;">🗑️</span> Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                            <div class="paginacion">
                                <?php if ($pagina_actual > 1): ?>
                                    <a href="?pagina=<?php echo $pagina_actual - 1; ?>" class="btn-small">&laquo; Anterior</a>
                                <?php endif; ?>
                                
                                <?php 
                                $rango = 2;
                                $inicio_pag = max(1, $pagina_actual - $rango);
                                $fin_pag = min($total_paginas, $pagina_actual + $rango);
                                
                                if ($inicio_pag > 1): ?>
                                    <a href="?pagina=1" class="btn-small">1</a>
                                    <?php if ($inicio_pag > 2): ?>
                                        <span style="padding: 0 10px;">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
                                    <a href="?pagina=<?php echo $i; ?>" 
                                       class="btn-small <?php echo ($pagina_actual == $i) ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($fin_pag < $total_paginas): ?>
                                    <?php if ($fin_pag < $total_paginas - 1): ?>
                                        <span style="padding: 0 10px;">...</span>
                                    <?php endif; ?>
                                    <a href="?pagina=<?php echo $total_paginas; ?>" class="btn-small"><?php echo $total_paginas; ?></a>
                                <?php endif; ?>
                                
                                <?php if ($pagina_actual < $total_paginas): ?>
                                    <a href="?pagina=<?php echo $pagina_actual + 1; ?>" class="btn-small">Siguiente &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div style="text-align: center; padding: 60px 20px;">
                            <div style="font-size: 72px; margin-bottom: 20px;">📁</div>
                            <h3 style="color: #495057; margin-bottom: 10px;">No hay categorías registradas</h3>
                            <p style="color: #6c757d; margin-bottom: 30px;">
                                Comienza creando tu primera categoría para organizar tus productos
                            </p>
                            <a href="categoria_nuevo.php" class="btn btn-primary">
                                <span style="margin-right: 5px;">+</span> Agregar Primera Categoría
                            </a>
                        </div>
                    <?php endif; ?>
                </section>
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