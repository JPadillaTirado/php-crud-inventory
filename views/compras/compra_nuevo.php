<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
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

// Generar código automático para la compra
$year = date('Y');
$sql_ultimo = "SELECT MAX(CAST(SUBSTRING(compra_codigo, -3) AS UNSIGNED)) as ultimo_numero 
               FROM compra 
               WHERE compra_codigo LIKE 'ORD-$year-%'";
$resultado_ultimo = $conexion->query($sql_ultimo);
$ultimo_numero = $resultado_ultimo->fetch_assoc()['ultimo_numero'] ?? 0;
$nuevo_numero = $ultimo_numero + 1;
$codigo_sugerido = "ORD-$year-" . str_pad($nuevo_numero, 3, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Nueva Compra - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales para formularios -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de nueva compra */
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
        
        /* Card de ayuda */
        .help-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .help-card h4 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .help-card p {
            margin: 0 0 15px 0;
            opacity: 0.9;
        }
        
        .help-examples {
            margin: 8px 0;
        }
        
        .help-examples strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .help-examples span {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 15px;
            margin-right: 10px;
            margin-bottom: 5px;
            font-size: 14px;
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
        
        /* Controles de productos */
        .producto-row {
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 5px;
            margin-bottom: 20px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .producto-row:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
            padding: 0;
            cursor: pointer;
            font-size: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            line-height: 1;
            text-align: center;
            vertical-align: middle;
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
            transition: all 0.3s ease;
        }
        
        .btn-add-product:hover {
            background-color: #229954;
            transform: translateY(-1px);
        }
        
        /* Producto vacío */
        .productos-empty {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .productos-empty .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Total */
        .total-section {
            background-color: #2c3e50;
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        .total-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .total-count {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 10px;
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
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover:not(:disabled) {
            background-color: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .search-result-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.2s ease;
        }
        
        .search-result-item:hover {
            background-color: #f8f9fa;
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-item strong {
            color: #2c3e50;
        }
        
        .search-result-item small {
            color: #6c757d;
        }
        
        /* Animaciones */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .producto-row {
            animation: slideIn 0.3s ease;
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
                        <span style="margin-right: 10px;">🛒</span>Nueva Compra
                    </h1>
                    <p style="color: #6c757d; font-size: 16px;">Crea una nueva orden de compra para un proveedor</p>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mensaje error" style="max-width: 900px; margin: 0 auto 20px;">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Tarjeta de ayuda -->
                <div class="help-card" style="max-width: 900px; margin: 0 auto 25px;">
                    <h4>💡 Guía para crear una compra</h4>
                    <p>Las compras permiten registrar órdenes de productos a proveedores. 
                       Una vez creada, podrás marcarla como "recibida" para actualizar automáticamente el inventario.</p>
                    <div class="help-examples">
                        <strong>Pasos básicos:</strong>
                        <span>1. Seleccionar proveedor</span>
                        <span>2. Agregar productos</span>
                        <span>3. Verificar totales</span>
                        <span>4. Guardar compra</span>
                    </div>
                    <div class="help-examples">
                        <strong>Recuerda:</strong>
                        <span>Código único</span>
                        <span>Fechas correctas</span>
                        <span>Precios de compra</span>
                        <span>Cantidades exactas</span>
                    </div>
                </div>

                <div class="formulario-card">
                    <form action="../../controllers/compra_guardar.php" method="POST" id="newForm">
                        
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
                                   value="<?php echo isset($_GET['codigo']) ? htmlspecialchars($_GET['codigo']) : $codigo_sugerido; ?>"
                                   placeholder="ORD-2025-001">
                            <small>Código único para identificar esta compra. Se sugiere: <strong><?php echo $codigo_sugerido; ?></strong></small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="proveedor" class="required">
                                <span style="margin-right: 5px;">🏢</span>Proveedor:
                            </label>
                            <select id="proveedor" name="proveedor_id" required>
                                <option value="">Seleccionar proveedor...</option>
                                <?php while ($prov = $proveedores->fetch_assoc()): ?>
                                    <option value="<?php echo $prov['proveedor_id']; ?>"
                                            <?php echo (isset($_GET['proveedor_id']) && $_GET['proveedor_id'] == $prov['proveedor_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prov['proveedor_nombre'] . ' - ' . $prov['proveedor_rut_nit']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small>Selecciona el proveedor de esta compra. Solo se muestran proveedores activos.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="fecha" class="required">
                                <span style="margin-right: 5px;">📅</span>Fecha de Compra:
                            </label>
                            <input type="date" 
                                   id="fecha" 
                                   name="fecha" 
                                   value="<?php echo isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d'); ?>"
                                   required>
                            <small>Fecha en que se realizó o realizará la compra.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="observaciones">
                                <span style="margin-right: 5px;">📝</span>Observaciones:
                            </label>
                            <textarea id="observaciones" 
                                      name="observaciones" 
                                      rows="3" 
                                      placeholder="Observaciones adicionales sobre esta compra..."><?php echo isset($_GET['observaciones']) ? htmlspecialchars($_GET['observaciones']) : ''; ?></textarea>
                            <small>Información adicional o notas sobre esta compra (opcional).</small>
                        </div>

                        <!-- Sección de productos -->
                        <div class="productos-section">
                            <h3>📦 Productos de la Compra</h3>
                            
                            <div id="productos-container">
                                <div class="productos-empty" id="productos-empty">
                                    <div class="empty-icon">📦</div>
                                    <h4>No hay productos agregados</h4>
                                    <p>Haz clic en "Agregar Producto" para comenzar a armar tu orden de compra</p>
                                </div>
                            </div>
                            
                            <button type="button" class="btn-add-product" onclick="agregarProducto()">
                                <span style="margin-right: 8px;">+</span> Agregar Producto
                            </button>
                        </div>

                        <!-- Total -->
                        <div class="total-section">
                            <div class="total-value" id="total-display">$0</div>
                            <div>Total de la Compra</div>
                            <div class="total-count" id="total-count">0 productos</div>
                            <input type="hidden" id="total-hidden" name="total" value="0">
                        </div>

                        <!-- Botones de acción -->
                        <div class="acciones-formulario">
                            <a href="compra_listar.php" class="btn btn-secondary">
                                <span style="margin-right: 8px;">←</span> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
                                <span style="margin-right: 8px;">💾</span> Crear Compra
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
        let contadorProductos = 0;

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
            // Event listeners para verificar estado del botón
            const form = document.getElementById('newForm');
            const inputs = form.querySelectorAll('input[required], select[required]');
            
            inputs.forEach(input => {
                input.addEventListener('input', checkFormStatus);
                input.addEventListener('change', checkFormStatus);
            });
            
            // Verificación inicial
            checkFormStatus();
        });

        function crearFilaProducto(index) {
            return `
                <div class="producto-row" id="producto-${index}">
                    <div class="producto-controls">
                        <div>
                            <label>Producto:</label>
                            <div class="producto-search">
                                <input type="text" 
                                       name="productos[${index}][nombre]" 
                                       placeholder="Buscar producto por nombre o código..."
                                       oninput="buscarProducto(${index}, this.value)"
                                       autocomplete="off"
                                       required>
                                <div class="search-results" id="results-${index}"></div>
                                <input type="hidden" name="productos[${index}][id]" required>
                            </div>
                        </div>
                        <div>
                            <label>Cantidad:</label>
                            <input type="number" 
                                   name="productos[${index}][cantidad]" 
                                   placeholder="0"
                                   min="1" 
                                   oninput="calcularSubtotal(${index})"
                                   required>
                        </div>
                        <div>
                            <label>Precio Compra:</label>
                            <input type="number" 
                                   name="productos[${index}][precio]" 
                                   placeholder="0.00"
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
                                   value="$0"
                                   style="background-color: #f8f9fa;">
                        </div>
                        <div>
                            <label>&nbsp;</label>
                            <button type="button" class="btn-remove" onclick="removerProducto(${index})" title="Eliminar producto">
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function agregarProducto() {
            const container = document.getElementById('productos-container');
            const emptyMessage = document.getElementById('productos-empty');
            
            // Ocultar mensaje vacío si es el primer producto
            if (emptyMessage) {
                emptyMessage.style.display = 'none';
            }
            
            const productoHtml = crearFilaProducto(contadorProductos);
            container.insertAdjacentHTML('beforeend', productoHtml);
            contadorProductos++;
            
            // Focus en el campo de búsqueda del nuevo producto
            const nuevoInput = document.querySelector(`input[name="productos[${contadorProductos - 1}][nombre]"]`);
            if (nuevoInput) {
                nuevoInput.focus();
            }
            
            checkFormStatus();
        }

        function removerProducto(index) {
            const elemento = document.getElementById(`producto-${index}`);
            if (elemento) {
                elemento.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    elemento.remove();
                    calcularTotal();
                    checkFormStatus();
                    
                    // Mostrar mensaje vacío si no hay productos
                    const container = document.getElementById('productos-container');
                    const productosRestantes = container.querySelectorAll('.producto-row');
                    if (productosRestantes.length === 0) {
                        document.getElementById('productos-empty').style.display = 'block';
                    }
                }, 300);
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
                resultsContainer.innerHTML = '<div class="search-result-item"><em>No se encontraron productos</em></div>';
                resultsContainer.style.display = 'block';
                return;
            }
            
            let html = '';
            filtrados.slice(0, 8).forEach(producto => {
                html += `
                    <div class="search-result-item" onclick="seleccionarProducto(${index}, ${producto.producto_id}, '${producto.producto_nombre.replace(/'/g, "\\'")}', ${producto.producto_precio})">
                        <strong>${producto.producto_nombre}</strong><br>
                        <small>Código: ${producto.producto_codigo} | Precio sugerido: $${formatNumber(producto.producto_precio)}</small>
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
            
            // Focus en el campo cantidad
            const cantidadInput = document.querySelector(`input[name="productos[${index}][cantidad]"]`);
            if (cantidadInput) {
                cantidadInput.focus();
            }
            
            calcularSubtotal(index);
            checkFormStatus();
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
            checkFormStatus();
        }

        function calcularTotal() {
            let total = 0;
            let countProductos = 0;
            const subtotalInputs = document.querySelectorAll('input[name*="[subtotal]"]');
            
            subtotalInputs.forEach(input => {
                const valor = input.value.replace(/[$,.]/g, '');
                const subtotal = parseFloat(valor) || 0;
                if (subtotal > 0) {
                    total += subtotal;
                    countProductos++;
                }
            });
            
            document.getElementById('total-display').textContent = '$' + formatNumber(total);
            document.getElementById('total-count').textContent = countProductos + ' producto' + (countProductos !== 1 ? 's' : '');
            document.getElementById('total-hidden').value = total;
        }

        function formatNumber(num) {
            return Math.round(num).toLocaleString('es-CO');
        }

        function checkFormStatus() {
            const form = document.getElementById('newForm');
            const saveBtn = document.getElementById('saveBtn');
            
            // Verificar campos básicos
            const requiredInputs = form.querySelectorAll('input[required]:not([name*="[id]"]), select[required]');
            let basicFieldsValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    basicFieldsValid = false;
                }
            });
            
            // Verificar que hay al menos un producto válido
            const productosValidos = obtenerProductosValidos();
            const hasValidProducts = productosValidos.length > 0;
            
            // Verificar total mayor que cero
            const total = parseFloat(document.getElementById('total-hidden').value) || 0;
            const hasValidTotal = total > 0;
            
            // Habilitar botón solo si todo está válido
            const isFormValid = basicFieldsValid && hasValidProducts && hasValidTotal;
            saveBtn.disabled = !isFormValid;
            
            // Cambiar texto del botón según estado
            if (!basicFieldsValid) {
                saveBtn.innerHTML = '<span style="margin-right: 8px;">⚠️</span> Completar información básica';
            } else if (!hasValidProducts) {
                saveBtn.innerHTML = '<span style="margin-right: 8px;">📦</span> Agregar productos';
            } else if (!hasValidTotal) {
                saveBtn.innerHTML = '<span style="margin-right: 8px;">💰</span> Verificar totales';
            } else {
                saveBtn.innerHTML = '<span style="margin-right: 8px;">💾</span> Crear Compra';
            }
        }

        function obtenerProductosValidos() {
            const productos = [];
            const rows = document.querySelectorAll('.producto-row');
            
            rows.forEach(row => {
                const inputs = row.querySelectorAll('input');
                const producto = {};
                
                inputs.forEach(input => {
                    if (input.name.includes('[id]')) producto.id = input.value;
                    if (input.name.includes('[cantidad]')) producto.cantidad = input.value;
                    if (input.name.includes('[precio]')) producto.precio = input.value;
                });
                
                if (producto.id && producto.cantidad && producto.precio && 
                    parseFloat(producto.cantidad) > 0 && parseFloat(producto.precio) > 0) {
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

        // Animación de salida para productos eliminados
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(-100%);
                }
            }
        `;
        document.head.appendChild(style);

        // Agregar primer producto automáticamente
        document.addEventListener('DOMContentLoaded', function() {
            // Pequeño delay para que se cargue todo
            setTimeout(() => {
                agregarProducto();
            }, 500);
        });
    </script>
</body>
</html>