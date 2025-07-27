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

// Consultar datos de la compra
$sql_compra = "SELECT 
                c.*,
                p.proveedor_nombre,
                p.proveedor_rut_nit
            FROM compra c
            INNER JOIN proveedor p ON c.proveedor_id = p.proveedor_id
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
    $mensaje = 'Solo se pueden editar compras en estado pendiente';
    header("Location: compra_ver.php?id=$compra_id&mensaje=$mensaje&tipo=error");
    exit;
}

// Obtener proveedores activos para el select
$sql_proveedores = "SELECT proveedor_id, proveedor_nombre, proveedor_rut_nit 
                   FROM proveedor 
                   WHERE proveedor_estado = 1 
                   ORDER BY proveedor_nombre";
$proveedores = $conexion->query($sql_proveedores);

// Obtener productos disponibles para el autocomplete
$sql_productos = "SELECT producto_id, producto_codigo, producto_nombre, producto_precio 
                 FROM producto 
                 ORDER BY producto_nombre";
$productos = $conexion->query($sql_productos);

// Obtener productos ya agregados a esta compra
$sql_detalles = "SELECT 
                    dc.*,
                    pr.producto_codigo,
                    pr.producto_nombre,
                    pr.producto_precio as precio_actual
                FROM detalle_compra dc
                INNER JOIN producto pr ON dc.producto_id = pr.producto_id
                WHERE dc.compra_id = ?
                ORDER BY pr.producto_nombre";

$stmt_detalles = $conexion->prepare($sql_detalles);
$stmt_detalles->bind_param("i", $compra_id);
$stmt_detalles->execute();
$resultado_detalles = $stmt_detalles->get_result();

$productos_existentes = array();
while ($detalle = $resultado_detalles->fetch_assoc()) {
    $productos_existentes[] = $detalle;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Editar Compra <?php echo htmlspecialchars($compra['compra_codigo']); ?> - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales para formularios -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de editar compra */
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
        
        .formulario-card {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            max-width: 900px;
            margin: 0 auto;
        }
        
        /* Ajuste para los mensajes */
        .mensaje {
            margin-bottom: 20px;
        }
        
        /* Ajustes para el formulario */
        .grupo-formulario {
            margin-bottom: 25px;
        }
        
        .grupo-formulario label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .grupo-formulario input, .grupo-formulario select, .grupo-formulario textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .grupo-formulario input:focus, .grupo-formulario select:focus, .grupo-formulario textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .grupo-formulario small {
            display: block;
            margin-top: 6px;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        /* Información de la compra */
        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .info-card h4 {
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Sección de productos */
        .productos-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .productos-section h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 18px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        
        /* Tabla de productos */
        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .productos-table th {
            background-color: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        .productos-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .productos-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Controles de productos */
        .producto-row {
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 5px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .producto-controls {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: end;
        }
        
        .btn-remove {
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px;
            cursor: pointer;
            font-size: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            line-height: 1;
        }
        
        .btn-remove:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
        }
        
        .btn-add-product {
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin-top: 15px;
        }
        
        .btn-add-product:hover {
            background-color: #229954;
        }
        
        /* Total */
        .total-section {
            background-color: #2c3e50;
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .total-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        /* Botones principales */
        .acciones-formulario {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #eee;
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
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            background-color: #2980b9;
            transform: translateY(-1px);
        }
        
        .btn-primary:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        /* Campos obligatorios */
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        
        /* Campo de búsqueda de productos */
        .producto-search {
            position: relative;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .search-result-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .search-result-item:hover {
            background-color: #f8f9fa;
        }
        
        .search-result-item:last-child {
            border-bottom: none;
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
            
            .formulario-card {
                margin: 0;
                border-radius: 0;
            }
            
            .acciones-formulario {
                flex-direction: column;
            }
            
            .btn {
                text-align: center;
                justify-content: center;
            }
            
            .producto-controls {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .info-grid {
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
                        <span style="margin-right: 10px;">✏️</span>Editar Compra
                    </h1>
                    <p style="color: #6c757d; font-size: 16px;">Modifica la orden de compra <?php echo htmlspecialchars($compra['compra_codigo']); ?></p>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mensaje error" style="max-width: 900px; margin: 0 auto 20px;">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Información actual de la compra -->
                <div class="info-card" style="max-width: 900px; margin: 0 auto 25px;">
                    <h4>📊 Información Actual de la Compra</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-value"><?php echo htmlspecialchars($compra['compra_codigo']); ?></span>
                            <span class="info-label">Código de Compra</span>
                        </div>
                        <div class="info-item">
                            <span class="info-value"><?php echo htmlspecialchars($compra['proveedor_nombre']); ?></span>
                            <span class="info-label">Proveedor</span>
                        </div>
                        <div class="info-item">
                            <span class="info-value">$<?php echo number_format($compra['compra_total'], 0, ',', '.'); ?></span>
                            <span class="info-label">Total Actual</span>
                        </div>
                        <div class="info-item">
                            <span class="info-value"><?php echo count($productos_existentes); ?></span>
                            <span class="info-label">Productos</span>
                        </div>
                    </div>
                </div>

                <div class="formulario-card">
                    <form action="../../controllers/compra_actualizar.php" method="POST" id="editForm">
                        <input type="hidden" name="compra_id" value="<?php echo $compra_id; ?>">
                        
                        <!-- Información básica -->
                        <div class="grupo-formulario">
                            <label for="codigo" class="required">
                                <span style="margin-right: 5px;">🏷️</span>Código de Compra:
                            </label>
                            <input type="text" 
                                   id="codigo" 
                                   name="codigo" 
                                   maxlength="20" 
                                   required
                                   value="<?php echo htmlspecialchars($compra['compra_codigo']); ?>"
                                   data-original="<?php echo htmlspecialchars($compra['compra_codigo']); ?>">
                            <small>Código único para identificar esta compra (ej: ORD-2025-001).</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="proveedor" class="required">
                                <span style="margin-right: 5px;">🏢</span>Proveedor:
                            </label>
                            <select id="proveedor" name="proveedor_id" required>
                                <option value="">Seleccionar proveedor...</option>
                                <?php 
                                $proveedores->data_seek(0); // Reset pointer
                                while ($prov = $proveedores->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $prov['proveedor_id']; ?>"
                                            <?php echo ($prov['proveedor_id'] == $compra['proveedor_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prov['proveedor_nombre'] . ' - ' . $prov['proveedor_rut_nit']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small>Selecciona el proveedor de esta compra.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="fecha">
                                <span style="margin-right: 5px;">📅</span>Fecha de Compra:
                            </label>
                            <input type="date" 
                                   id="fecha" 
                                   name="fecha" 
                                   value="<?php echo $compra['compra_fecha']; ?>"
                                   data-original="<?php echo $compra['compra_fecha']; ?>"
                                   required>
                            <small>Fecha en que se realizó la compra.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="observaciones">
                                <span style="margin-right: 5px;">📝</span>Observaciones:
                            </label>
                            <textarea id="observaciones" 
                                      name="observaciones" 
                                      rows="3" 
                                      placeholder="Observaciones adicionales sobre esta compra..."
                                      data-original="<?php echo htmlspecialchars($compra['compra_observaciones']); ?>"><?php echo htmlspecialchars($compra['compra_observaciones']); ?></textarea>
                            <small>Información adicional o notas sobre esta compra (opcional).</small>
                        </div>

                        <!-- Sección de productos -->
                        <div class="productos-section">
                            <h3>📦 Productos de la Compra</h3>
                            
                            <div id="productos-container">
                                <!-- Productos existentes se cargarán aquí -->
                            </div>
                            
                            <button type="button" class="btn-add-product" onclick="agregarProducto()">
                                <span style="margin-right: 8px;">+</span> Agregar Producto
                            </button>
                        </div>

                        <!-- Total -->
                        <div class="total-section">
                            <div class="total-value" id="total-display">$0</div>
                            <div>Total de la Compra</div>
                            <input type="hidden" id="total-hidden" name="total" value="0">
                        </div>

                        <!-- Botones de acción -->
                        <div class="acciones-formulario">
                            <a href="compra_ver.php?id=<?php echo $compra_id; ?>" class="btn btn-secondary">
                                <span style="margin-right: 8px;">←</span> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
                                <span style="margin-right: 8px;">💾</span> Actualizar Compra
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Variables globales
        let productosDisponibles = <?php echo json_encode($productos->fetch_all(MYSQLI_ASSOC)); ?>;
        let productosExistentes = <?php echo json_encode($productos_existentes); ?>;
        let contadorProductos = 0;
        let datosOriginales = {};

        // Script mejorado para el manejo del sidebar
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
            
            if (window.innerWidth <= 768) {
                document.addEventListener('click', function(e) {
                    const isClickInsideSidebar = sidebar.contains(e.target);
                    const isClickOnToggle = sidebarToggle.contains(e.target);
                    
                    if (!isClickInsideSidebar && !isClickOnToggle && !sidebar.classList.contains('collapsed')) {
                        toggleSidebar();
                    }
                });
            }
            
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

        // Funciones para manejar productos
        document.addEventListener('DOMContentLoaded', function() {
            // Guardar datos originales
            const form = document.getElementById('editForm');
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.dataset.original !== undefined) {
                    datosOriginales[input.name] = input.dataset.original;
                }
            });
            
            // Cargar productos existentes
            cargarProductosExistentes();
            
            // Event listeners para detectar cambios
            inputs.forEach(input => {
                input.addEventListener('input', checkChanges);
                input.addEventListener('change', checkChanges);
            });
            
            checkChanges();
        });

        function cargarProductosExistentes() {
            const container = document.getElementById('productos-container');
            container.innerHTML = '';
            
            productosExistentes.forEach((producto, index) => {
                const productoHtml = crearFilaProducto(index, producto);
                container.insertAdjacentHTML('beforeend', productoHtml);
                contadorProductos = index + 1;
            });
            
            calcularTotal();
        }

        function crearFilaProducto(index, producto = null) {
            const productoData = producto || {};
            return `
                <div class="producto-row" id="producto-${index}">
                    <div class="producto-controls">
                        <div>
                            <label>Producto:</label>
                            <div class="producto-search">
                                <input type="text" 
                                       name="productos[${index}][nombre]" 
                                       placeholder="Buscar producto..."
                                       value="${productoData.producto_nombre || ''}"
                                       oninput="buscarProducto(${index}, this.value)"
                                       autocomplete="off">
                                <div class="search-results" id="results-${index}"></div>
                                <input type="hidden" name="productos[${index}][id]" value="${productoData.producto_id || ''}">
                            </div>
                        </div>
                        <div>
                            <label>Cantidad:</label>
                            <input type="number" 
                                   name="productos[${index}][cantidad]" 
                                   placeholder="0"
                                   value="${productoData.detalle_cantidad || ''}"
                                   min="1" 
                                   oninput="calcularSubtotal(${index})"
                                   required>
                        </div>
                        <div>
                            <label>Precio Compra:</label>
                            <input type="number" 
                                   name="productos[${index}][precio]" 
                                   placeholder="0.00"
                                   value="${productoData.detalle_precio_compra || ''}"
                                   step="0.01" 
                                   min="0"
                                   oninput="calcularSubtotal(${index})"
                                   required>
                        </div>
                        <div>
                            <label>Subtotal:</label>
                            <input type="text" 
                                   name="productos[${index}][subtotal]" 
                                   readonly 
                                   value="$${formatNumber(productoData.detalle_subtotal || 0)}"
                                   style="background-color: #f8f9fa;">
                        </div>
                        <div>
                            <label>&nbsp;</label>
                            <button type="button" class="btn-remove" onclick="removerProducto(${index})">
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function agregarProducto() {
            const container = document.getElementById('productos-container');
            const productoHtml = crearFilaProducto(contadorProductos);
            container.insertAdjacentHTML('beforeend', productoHtml);
            contadorProductos++;
            checkChanges();
        }

        function removerProducto(index) {
            const elemento = document.getElementById(`producto-${index}`);
            if (elemento) {
                elemento.remove();
                calcularTotal();
                checkChanges();
            }
        }

        function buscarProducto(index, query) {
            const resultsContainer = document.getElementById(`results-${index}`);
            
            if (query.length < 2) {
                resultsContainer.style.display = 'none';
                return;
            }
            
            const filtrados = productosDisponibles.filter(producto => 
                producto.producto_nombre.toLowerCase().includes(query.toLowerCase()) ||
                producto.producto_codigo.toLowerCase().includes(query.toLowerCase())
            );
            
            if (filtrados.length === 0) {
                resultsContainer.style.display = 'none';
                return;
            }
            
            let html = '';
            filtrados.slice(0, 5).forEach(producto => {
                html += `
                    <div class="search-result-item" onclick="seleccionarProducto(${index}, ${producto.producto_id}, '${producto.producto_nombre}', ${producto.producto_precio})">
                        <strong>${producto.producto_nombre}</strong><br>
                        <small>Código: ${producto.producto_codigo} | Precio: $${formatNumber(producto.producto_precio)}</small>
                    </div>
                `;
            });
            
            resultsContainer.innerHTML = html;
            resultsContainer.style.display = 'block';
        }

        function seleccionarProducto(index, id, nombre, precio) {
            const nombreInput = document.querySelector(`input[name="productos[${index}][nombre]"]`);
            const idInput = document.querySelector(`input[name="productos[${index}][id]"]`);
            const precioInput = document.querySelector(`input[name="productos[${index}][precio]"]`);
            const resultsContainer = document.getElementById(`results-${index}`);
            
            nombreInput.value = nombre;
            idInput.value = id;
            precioInput.value = precio;
            resultsContainer.style.display = 'none';
            
            calcularSubtotal(index);
            checkChanges();
        }

        function calcularSubtotal(index) {
            const cantidadInput = document.querySelector(`input[name="productos[${index}][cantidad]"]`);
            const precioInput = document.querySelector(`input[name="productos[${index}][precio]"]`);
            const subtotalInput = document.querySelector(`input[name="productos[${index}][subtotal]"]`);
            
            const cantidad = parseFloat(cantidadInput.value) || 0;
            const precio = parseFloat(precioInput.value) || 0;
            const subtotal = cantidad * precio;
            
            subtotalInput.value = '$' + formatNumber(subtotal);
            
            calcularTotal();
            checkChanges();
        }

        function calcularTotal() {
            let total = 0;
            const subtotalInputs = document.querySelectorAll('input[name*="[subtotal]"]');
            
            subtotalInputs.forEach(input => {
                const valor = input.value.replace(/[$,.]/g, '');
                total += parseFloat(valor) || 0;
            });
            
            document.getElementById('total-display').textContent = '$' + formatNumber(total);
            document.getElementById('total-hidden').value = total;
        }

        function formatNumber(num) {
            return Math.round(num).toLocaleString('es-CO');
        }

        function checkChanges() {
            const form = document.getElementById('editForm');
            const saveBtn = document.getElementById('saveBtn');
            let hasChanges = false;
            
            // Verificar campos básicos
            const inputs = form.querySelectorAll('input[data-original], select[data-original], textarea[data-original]');
            inputs.forEach(input => {
                const original = input.dataset.original || '';
                const current = input.value || '';
                
                if (original !== current) {
                    hasChanges = true;
                }
            });
            
            // Verificar productos (comparar con productos originales)
            const productosActuales = obtenerProductosActuales();
            if (JSON.stringify(productosActuales) !== JSON.stringify(productosExistentes)) {
                hasChanges = true;
            }
            
            saveBtn.disabled = !hasChanges;
        }

        function obtenerProductosActuales() {
            const productos = [];
            const rows = document.querySelectorAll('.producto-row');
            
            rows.forEach(row => {
                const inputs = row.querySelectorAll('input');
                const producto = {};
                
                inputs.forEach(input => {
                    if (input.name.includes('[id]')) producto.producto_id = input.value;
                    if (input.name.includes('[cantidad]')) producto.detalle_cantidad = input.value;
                    if (input.name.includes('[precio]')) producto.detalle_precio_compra = input.value;
                });
                
                if (producto.producto_id && producto.detalle_cantidad && producto.detalle_precio_compra) {
                    productos.push(producto);
                }
            });
            
            return productos;
        }

        // Cerrar resultados de búsqueda al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.producto-search')) {
                document.querySelectorAll('.search-results').forEach(results => {
                    results.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>