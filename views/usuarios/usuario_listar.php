<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Verificación de autenticación

// Obtener información del usuario logueado
$usuario_actual = getUsuarioLogueado();
$nombre_completo = getNombreCompleto();

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}

// Configuración de paginación
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$usuarios_por_pagina = ITEMS_POR_PAGINA;
$inicio = ($pagina_actual - 1) * $usuarios_por_pagina;

// Obtener el total de usuarios para la paginación
$sql_total = "SELECT COUNT(*) as total FROM usuario";
$resultado_total = $conexion->query($sql_total);
$total_usuarios = $resultado_total->fetch_assoc()['total'];
$total_paginas = ceil($total_usuarios / $usuarios_por_pagina);

// Obtener usuarios
$sql = "SELECT * FROM usuario ORDER BY usuario_nombre ASC LIMIT $inicio, $usuarios_por_pagina";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Listado de Usuarios - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de usuarios */
        .dashboard-container {
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        
        .main-content {
            padding-top: 0;
        }
        
        .usuarios-header {
            background-color: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .usuarios-content {
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
        
        /* Badges para el estado del usuario */
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
        
        .badge.badge-info {
            background-color: #e3f2fd;
            color: #0288d1;
        }
        
        /* Estilos para el avatar del usuario */
        .user-avatar-small {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            line-height: 35px;
            font-weight: 600;
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
        
        /* Información de resumen */
        .summary-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .summary-info p {
            margin: 0;
            color: #495057;
        }
        
        /* Indicador de usuario actual */
        .current-user-indicator {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 8px;
        }
        
        /* Header de acciones */
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .usuarios-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .usuarios-content {
                padding: 20px;
            }
            
            table {
                font-size: 14px;
            }
            
            .btn-small {
                padding: 4px 8px;
                font-size: 12px;
            }
            
            .user-avatar-small {
                width: 30px;
                height: 30px;
                line-height: 30px;
                font-size: 12px;
            }
            
            /* Ocultar columna de productos en móviles */
            @media (max-width: 576px) {
                .hide-mobile {
                    display: none;
                }
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
                <a href="../../facturas_listar.php" class="nav-item" title="Facturación">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-text">Facturación</span>
                </a>
                <a href="../../en_construccion.php?modulo=configuraciones" class="nav-item" title="Configuraciones">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Configuraciones</span>
                </a>
                <a href="usuario_listar.php" class="nav-item active" title="Usuarios">
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
            <div class="usuarios-header">
                <div>
                    <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">
                        <span style="margin-right: 10px;">👥</span>Listado de Usuarios
                    </h1>
                    <p style="color: #6c757d; font-size: 16px;">Administra los usuarios del sistema</p>
                </div>
                <div class="header-actions">
                    <a href="usuario_nuevo.php" class="btn btn-primary">
                        <span style="margin-right: 5px;">+</span> Agregar Nuevo Usuario
                    </a>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="usuarios-content">
                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="mensaje <?php echo $_GET['tipo'] ?? 'info'; ?>">
                        <?php 
                        $icon = '✓';
                        if (($_GET['tipo'] ?? 'info') == 'error') $icon = '⚠️';
                        elseif (($_GET['tipo'] ?? 'info') == 'exito') $icon = '✅';
                        ?>
                        <strong><?php echo $icon; ?></strong> <?php echo htmlspecialchars($_GET['mensaje']); ?>
                    </div>
                <?php endif; ?>

                <section class="tabla-resultados">
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <!-- Información de resumen -->
                        <div class="summary-info">
                            <p>
                                Mostrando <strong><?php echo $inicio + 1; ?></strong> - 
                                <strong><?php echo min($inicio + $usuarios_por_pagina, $total_usuarios); ?></strong> 
                                de <strong><?php echo $total_usuarios; ?></strong> usuarios registrados
                            </p>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>Usuario</th>
                                    <th>Nombre Completo</th>
                                    <th>Email</th>
                                    <th class="hide-mobile">Productos</th>
                                    <th>Estado</th>
                                    <th style="text-align: right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($usuario = $resultado->fetch_assoc()): 
                                    // Consultar cantidad de productos registrados por este usuario
                                    $sql_productos = "SELECT COUNT(*) as total FROM producto WHERE usuario_id = " . $usuario['usuario_id'];
                                    $resultado_productos = $conexion->query($sql_productos);
                                    $total_productos = $resultado_productos ? $resultado_productos->fetch_assoc()['total'] : 0;
                                    
                                    // Generar iniciales para el avatar
                                    $iniciales = strtoupper(substr($usuario['usuario_nombre'], 0, 1) . substr($usuario['usuario_apellido'], 0, 1));
                                    
                                    // Verificar si es el usuario actual
                                    $es_usuario_actual = ($usuario['usuario_id'] == $usuario_actual['id']);
                                ?>
                                    <tr>
                                        <td><?php echo $usuario['usuario_id']; ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div class="user-avatar-small"><?php echo $iniciales; ?></div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($usuario['usuario_usuario']); ?></strong>
                                                    <?php if ($es_usuario_actual): ?>
                                                        <span class="current-user-indicator">Tú</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($usuario['usuario_nombre'] . ' ' . $usuario['usuario_apellido']); ?></td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($usuario['usuario_email']); ?>" 
                                               style="color: #2980b9; text-decoration: none;">
                                                <?php echo htmlspecialchars($usuario['usuario_email']); ?>
                                            </a>
                                        </td>
                                        <td class="hide-mobile">
                                            <?php if ($total_productos > 0): ?>
                                                <span class="badge badge-info">
                                                    <?php echo $total_productos; ?> producto<?php echo $total_productos != 1 ? 's' : ''; ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #6c757d;">Sin productos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">Activo</span>
                                        </td>
                                        <td class="acciones" style="text-align: right;">
                                            <a href="usuario_editar.php?id=<?php echo $usuario['usuario_id']; ?>" 
                                               class="btn-small" title="Editar usuario">
                                                <span style="margin-right: 3px;">✏️</span> Editar
                                            </a>
                                            <?php if (!$es_usuario_actual): ?>
                                                <a href="usuario_eliminar.php?id=<?php echo $usuario['usuario_id']; ?>" 
                                                   class="btn-small btn-danger" title="Eliminar usuario"
                                                   onclick="return confirm('¿Está seguro que desea eliminar este usuario?');">
                                                    <span style="margin-right: 3px;">🗑️</span> Eliminar
                                                </a>
                                            <?php else: ?>
                                                <button class="btn-small btn-danger" disabled title="No puedes eliminarte a ti mismo"
                                                        style="opacity: 0.5; cursor: not-allowed;">
                                                    <span style="margin-right: 3px;">🗑️</span> Eliminar
                                                </button>
                                            <?php endif; ?>
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
                            <div style="font-size: 72px; margin-bottom: 20px;">👥</div>
                            <h3 style="color: #495057; margin-bottom: 10px;">No hay usuarios registrados</h3>
                            <p style="color: #6c757d; margin-bottom: 30px;">
                                Comienza creando el primer usuario del sistema
                            </p>
                            <a href="usuario_nuevo.php" class="btn btn-primary">
                                <span style="margin-right: 5px;">+</span> Agregar Primer Usuario
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

        // Función para mostrar detalles del usuario en tooltip
        document.querySelectorAll('.user-avatar-small').forEach(avatar => {
            avatar.title = 'Usuario del sistema';
        });

        // Resaltar la fila al pasar el mouse
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    </script>
</body>
</html>