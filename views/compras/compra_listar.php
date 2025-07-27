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
$compras_por_pagina = ITEMS_POR_PAGINA;
$inicio = ($pagina_actual - 1) * $compras_por_pagina;

// Filtros opcionales
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_proveedor = isset($_GET['proveedor']) ? intval($_GET['proveedor']) : 0;

// Construir WHERE clause para filtros
$where_conditions = array();
$params = array();
$param_types = '';

if (!empty($filtro_estado)) {
    $where_conditions[] = "c.compra_estado = ?";
    $params[] = $filtro_estado;
    $param_types .= 's';
}

if ($filtro_proveedor > 0) {
    $where_conditions[] = "c.proveedor_id = ?";
    $params[] = $filtro_proveedor;
    $param_types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Contar total de compras con filtros
$sql_total = "SELECT COUNT(*) as total 
              FROM compra c 
              INNER JOIN proveedor p ON c.proveedor_id = p.proveedor_id 
              $where_clause";

if (!empty($params)) {
    $stmt_total = $conexion->prepare($sql_total);
    $stmt_total->bind_param($param_types, ...$params);
    $stmt_total->execute();
    $resultado_total = $stmt_total->get_result();
} else {
    $resultado_total = $conexion->query($sql_total);
}

$total_compras = $resultado_total->fetch_assoc()['total'];
$total_paginas = ceil($total_compras / $compras_por_pagina);

// Obtener compras con paginación y filtros
$sql = "SELECT 
            c.compra_id,
            c.compra_codigo,
            c.compra_fecha,
            c.compra_total,
            c.compra_estado,
            c.compra_observaciones,
            c.compra_fecha_registro,
            p.proveedor_nombre,
            p.proveedor_rut_nit,
            u.usuario_nombre,
            u.usuario_apellido
        FROM compra c
        INNER JOIN proveedor p ON c.proveedor_id = p.proveedor_id
        INNER JOIN usuario u ON c.usuario_id = u.usuario_id
        $where_clause
        ORDER BY c.compra_fecha_registro DESC
        LIMIT $inicio, $compras_por_pagina";

if (!empty($params)) {
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $resultado = $conexion->query($sql);
}

// Obtener lista de proveedores para el filtro
$sql_proveedores = "SELECT proveedor_id, proveedor_nombre FROM proveedor WHERE proveedor_estado = 1 ORDER BY proveedor_nombre";
$proveedores = $conexion->query($sql_proveedores);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Listado de Compras - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de compras */
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
        
        /* Estados de compras */
        .estado-pendiente {
            color: #f39c12;
            font-weight: 600;
        }
        .estado-recibida {
            color: #28a745;
            font-weight: 600;
        }
        .estado-cancelada {
            color: #dc3545;
            font-weight: 600;
        }
        .proveedor-info {
            font-size: 14px;
            color: #6c757d;
        }
        
        /* Filtros */
        .filtros-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .filtros-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filtro-grupo {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filtro-grupo label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        .filtro-grupo select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 150px;
        }
        
        .btn-filtrar {
            padding: 8px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn-limpiar {
            padding: 8px 20px;
            background-color: #95a5a6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        /* Badges para totales */
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
        
        .badge.badge-danger {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        /* Formato de moneda */
        .monto {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .monto.grande {
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
            
            table {
                font-size: 14px;
            }
            
            .btn-small {
                padding: 4px 8px;
                font-size: 12px;
            }
            
            .filtros-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filtro-grupo select {
                min-width: 100%;
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
                    <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">Listado de Compras</h1>
                    <p style="color: #6c757d; font-size: 16px;">Administra las órdenes de compra del sistema</p>
                </div>
                <a href="compra_nuevo.php" class="btn btn-primary">
                    <span style="margin-right: 5px;">+</span> Nueva Compra
                </a>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="mensaje <?php echo $_GET['tipo'] ?? 'info'; ?>">
                        <?php echo htmlspecialchars($_GET['mensaje']); ?>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="filtros-container">
                    <h4 style="margin: 0 0 15px 0; color: #495057;">🔍 Filtros de búsqueda</h4>
                    <form method="GET" class="filtros-row">
                        <div class="filtro-grupo">
                            <label>Estado:</label>
                            <select name="estado">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" <?php echo ($filtro_estado == 'pendiente') ? 'selected' : ''; ?>>⏳ Pendiente</option>
                                <option value="recibida" <?php echo ($filtro_estado == 'recibida') ? 'selected' : ''; ?>>✅ Recibida</option>
                                <option value="cancelada" <?php echo ($filtro_estado == 'cancelada') ? 'selected' : ''; ?>>❌ Cancelada</option>
                            </select>
                        </div>
                        
                        <div class="filtro-grupo">
                            <label>Proveedor:</label>
                            <select name="proveedor">
                                <option value="">Todos los proveedores</option>
                                <?php while ($prov = $proveedores->fetch_assoc()): ?>
                                    <option value="<?php echo $prov['proveedor_id']; ?>" 
                                            <?php echo ($filtro_proveedor == $prov['proveedor_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prov['proveedor_nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-filtrar">🔍 Filtrar</button>
                        <a href="compra_listar.php" class="btn-limpiar">🗑️ Limpiar</a>
                    </form>
                </div>

                <section class="tabla-resultados">
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <!-- Información de resumen -->
                        <div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px;">
                            <p style="margin: 0; color: #495057;">
                                Mostrando <strong><?php echo $inicio + 1; ?></strong> - 
                                <strong><?php echo min($inicio + $compras_por_pagina, $total_compras); ?></strong> 
                                de <strong><?php echo $total_compras; ?></strong> compras
                                <?php if (!empty($filtro_estado) || $filtro_proveedor > 0): ?>
                                    <span style="color: #3498db;">(filtradas)</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Proveedor</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Usuario</th>
                                    <th style="text-align: right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($compra = $resultado->fetch_assoc()): 
                                    // Formatear el total
                                    $total_formateado = number_format($compra['compra_total'], 0, ',', '.');
                                    $clase_monto = ($compra['compra_total'] > 100000) ? 'monto grande' : 'monto';
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($compra['compra_codigo']); ?></strong>
                                            <br>
                                            <small style="color: #6c757d;">ID: <?php echo $compra['compra_id']; ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($compra['proveedor_nombre']); ?></strong>
                                            <br>
                                            <small class="proveedor-info">
                                                <?php echo htmlspecialchars($compra['proveedor_rut_nit']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php 
                                            $fecha = new DateTime($compra['compra_fecha']);
                                            echo $fecha->format('d/m/Y');
                                            ?>
                                            <br>
                                            <small style="color: #6c757d;">
                                                <?php 
                                                $fecha_registro = new DateTime($compra['compra_fecha_registro']);
                                                echo 'Reg: ' . $fecha_registro->format('d/m/y H:i');
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="<?php echo $clase_monto; ?>">
                                                $<?php echo $total_formateado; ?>
                                            </span>
                                        </td>
                                        <td>
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
                                            <span class="<?php echo $estado_class; ?>">
                                                <?php echo $estado_icon . ' ' . $estado_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small style="color: #6c757d;">
                                                <?php echo htmlspecialchars($compra['usuario_nombre'] . ' ' . $compra['usuario_apellido']); ?>
                                            </small>
                                        </td>
                                        <td class="acciones" style="text-align: right;">
                                            <a href="compra_ver.php?id=<?php echo $compra['compra_id']; ?>" 
                                               class="btn btn-small btn-info" 
                                               title="Ver detalles de la compra"
                                               style="margin-right: 5px;">
                                                <span style="margin-right: 5px;">👁️</span> Ver
                                            </a>
                                            
                                            <?php if ($compra['compra_estado'] == 'pendiente'): ?>
                                                <a href="compra_editar.php?id=<?php echo $compra['compra_id']; ?>" 
                                                   class="btn btn-small btn-warning" 
                                                   title="Editar compra"
                                                   style="margin-right: 5px;">
                                                    <span style="margin-right: 5px;">✏️</span> Editar
                                                </a>
                                                <a href="compra_recibir.php?id=<?php echo $compra['compra_id']; ?>" 
                                                   class="btn btn-small btn-success" 
                                                   title="Marcar como recibida">
                                                    <span style="margin-right: 5px;">📦</span> Recibir
                                                </a>
                                            <?php elseif ($compra['compra_estado'] == 'recibida'): ?>
                                                <span class="btn btn-small" style="background-color: #e8f5e9; color: #388e3c;">
                                                    ✅ Completada
                                                </span>
                                            <?php else: ?>
                                                <span class="btn btn-small" style="background-color: #ffebee; color: #d32f2f;">
                                                    ❌ Cancelada
                                                </span>
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
                                    <a href="?pagina=<?php echo $pagina_actual - 1; ?>&estado=<?php echo $filtro_estado; ?>&proveedor=<?php echo $filtro_proveedor; ?>" class="btn-small">&laquo; Anterior</a>
                                <?php endif; ?>
                                
                                <?php 
                                $rango = 2;
                                $inicio_pag = max(1, $pagina_actual - $rango);
                                $fin_pag = min($total_paginas, $pagina_actual + $rango);
                                
                                if ($inicio_pag > 1): ?>
                                    <a href="?pagina=1&estado=<?php echo $filtro_estado; ?>&proveedor=<?php echo $filtro_proveedor; ?>" class="btn-small">1</a>
                                    <?php if ($inicio_pag > 2): ?>
                                        <span style="padding: 0 10px;">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
                                    <a href="?pagina=<?php echo $i; ?>&estado=<?php echo $filtro_estado; ?>&proveedor=<?php echo $filtro_proveedor; ?>" 
                                       class="btn-small <?php echo ($pagina_actual == $i) ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($fin_pag < $total_paginas): ?>
                                    <?php if ($fin_pag < $total_paginas - 1): ?>
                                        <span style="padding: 0 10px;">...</span>
                                    <?php endif; ?>
                                    <a href="?pagina=<?php echo $total_paginas; ?>&estado=<?php echo $filtro_estado; ?>&proveedor=<?php echo $filtro_proveedor; ?>" class="btn-small"><?php echo $total_paginas; ?></a>
                                <?php endif; ?>
                                
                                <?php if ($pagina_actual < $total_paginas): ?>
                                    <a href="?pagina=<?php echo $pagina_actual + 1; ?>&estado=<?php echo $filtro_estado; ?>&proveedor=<?php echo $filtro_proveedor; ?>" class="btn-small">Siguiente &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div style="text-align: center; padding: 60px 20px;">
                            <div style="font-size: 72px; margin-bottom: 20px;">🛒</div>
                            <h3 style="color: #495057; margin-bottom: 10px;">No hay compras registradas</h3>
                            <p style="color: #6c757d; margin-bottom: 30px;">
                                <?php if (!empty($filtro_estado) || $filtro_proveedor > 0): ?>
                                    No se encontraron compras con los filtros aplicados.
                                    <br><a href="compra_listar.php">Ver todas las compras</a>
                                <?php else: ?>
                                    Comienza registrando tu primera orden de compra
                                <?php endif; ?>
                            </p>
                            <?php if (empty($filtro_estado) && $filtro_proveedor == 0): ?>
                                <a href="compra_nuevo.php" class="btn btn-primary">
                                    <span style="margin-right: 5px;">+</span> Primera Compra
                                </a>
                            <?php endif; ?>
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