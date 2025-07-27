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
    header('Location: categoria_listar.php?mensaje=ID de categoría no válido&tipo=error');
    exit;
}

$categoria_id = intval($_GET['id']);

// Consultar datos de la categoría
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

// Obtener datos de la categoría
$categoria = $resultado->fetch_assoc();

// Obtener estadísticas de la categoría
$sql_productos = "SELECT COUNT(*) as total_productos FROM producto WHERE categoria_id = ?";
$stmt_productos = $conexion->prepare($sql_productos);
$stmt_productos->bind_param("i", $categoria_id);
$stmt_productos->execute();
$resultado_productos = $stmt_productos->get_result();
$total_productos = $resultado_productos->fetch_assoc()['total_productos'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Editar Categoría - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales para formularios -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de editar categoría */
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
        
        .formulario-card {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            max-width: 700px;
            margin: 0 auto;
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
        
        .grupo-formulario input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .grupo-formulario input:focus {
            border-color: #2980b9;
            outline: none;
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.1);
        }
        
        .grupo-formulario small {
            display: block;
            margin-top: 8px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .acciones-formulario {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
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
        
        /* Iconos para inputs */
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 16px;
            pointer-events: none;
        }
        
        .input-group input {
            padding-left: 45px;
        }
        
        /* Tarjeta de información */
        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }
        
        .info-card-content {
            position: relative;
            z-index: 2;
        }
        
        .info-card h4 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .info-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        
        .info-stat {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-stat .number {
            font-size: 24px;
            font-weight: 700;
        }
        
        .info-stat .label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Estado de cambios */
        .changes-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            opacity: 0;
            transform: translateY(100px);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .changes-indicator.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Animación del formulario */
        .formulario-card {
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
            
            .formulario-card {
                padding: 20px;
                margin: 0 10px;
            }
            
            .acciones-formulario {
                flex-direction: column-reverse;
            }
            
            .acciones-formulario .btn {
                width: 100%;
            }
            
            .info-stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .changes-indicator {
                right: 10px;
                left: 10px;
                text-align: center;
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
                    <span style="margin-right: 10px;">✏️</span>Editar Categoría
                    <span class="category-id-badge">ID: <?php echo $categoria_id; ?></span>
                </h1>
                <p style="color: #6c757d; font-size: 16px;">Modifica la información de la categoría</p>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mensaje error" style="max-width: 700px; margin: 0 auto 20px;">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Tarjeta de información de la categoría -->
                <div class="info-card" style="max-width: 700px; margin: 0 auto 25px;">
                    <div class="info-card-content">
                        <h4>📊 Información Actual de la Categoría</h4>
                        <p>Esta categoría fue creada y puede ser modificada desde este formulario.</p>
                        <div class="info-stats">
                            <div class="info-stat">
                                <span class="number"><?php echo $total_productos; ?></span>
                                <span class="label">Productos asociados</span>
                            </div>
                            <div class="info-stat">
                                <span class="number"><?php echo $categoria_id; ?></span>
                                <span class="label">ID único</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="formulario-card">
                    <form action="../../controllers/categoria_actualizar.php" method="POST" id="editForm">
                        <input type="hidden" name="categoria_id" value="<?php echo $categoria_id; ?>">
                        
                        <div class="grupo-formulario">
                            <label for="nombre">
                                <span style="margin-right: 5px;">🏷️</span>Nombre de la Categoría:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="nombre" 
                                       name="nombre" 
                                       maxlength="50" 
                                       required
                                       value="<?php echo htmlspecialchars($categoria['categoria_nombre']); ?>"
                                       data-original="<?php echo htmlspecialchars($categoria['categoria_nombre']); ?>">
                            </div>
                            <small>Máximo 50 caracteres. Este nombre aparecerá en el listado de productos.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="ubicacion">
                                <span style="margin-right: 5px;">📍</span>Ubicación Física:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="ubicacion" 
                                       name="ubicacion" 
                                       maxlength="150" 
                                       required
                                       value="<?php echo htmlspecialchars($categoria['categoria_ubicacion']); ?>"
                                       data-original="<?php echo htmlspecialchars($categoria['categoria_ubicacion']); ?>">
                            </div>
                            <small>Describe dónde se encuentran físicamente los productos de esta categoría.</small>
                        </div>
                        
                        <div class="acciones-formulario">
                            <a href="categoria_listar.php" class="btn btn-secondary">
                                <span style="margin-right: 5px;">←</span> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
                                <span style="margin-right: 5px;">💾</span> Actualizar Categoría
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Información adicional -->
                <?php if ($total_productos > 0): ?>
                    <div style="max-width: 700px; margin: 25px auto 0; text-align: center;">
                        <p style="color: #6c757d; font-size: 14px;">
                            ⚠️ <strong>Nota:</strong> Esta categoría tiene <strong><?php echo $total_productos; ?> producto(s)</strong> asociado(s). 
                            Los cambios se aplicarán a todos los productos de esta categoría.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Indicador de cambios -->
    <div class="changes-indicator" id="changesIndicator">
        ✏️ Tienes cambios sin guardar
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

        // Control de cambios en el formulario
        const nombreInput = document.getElementById('nombre');
        const ubicacionInput = document.getElementById('ubicacion');
        const saveBtn = document.getElementById('saveBtn');
        const changesIndicator = document.getElementById('changesIndicator');
        
        function checkForChanges() {
            const nombreChanged = nombreInput.value !== nombreInput.dataset.original;
            const ubicacionChanged = ubicacionInput.value !== ubicacionInput.dataset.original;
            const hasChanges = nombreChanged || ubicacionChanged;
            
            // Habilitar/deshabilitar botón de guardar
            saveBtn.disabled = !hasChanges;
            saveBtn.style.opacity = hasChanges ? '1' : '0.6';
            
            // Mostrar/ocultar indicador de cambios
            if (hasChanges) {
                changesIndicator.classList.add('show');
            } else {
                changesIndicator.classList.remove('show');
            }
        }
        
        // Event listeners para detectar cambios
        nombreInput.addEventListener('input', checkForChanges);
        ubicacionInput.addEventListener('input', checkForChanges);
        
        // Validación en tiempo real
        nombreInput.addEventListener('input', function() {
            const value = this.value.trim();
            const small = this.parentNode.nextElementSibling;
            
            if (value.length === 0) {
                small.style.color = '#e74c3c';
                small.textContent = 'El nombre de la categoría es obligatorio.';
            } else if (value.length > 50) {
                small.style.color = '#e74c3c';
                small.textContent = `Máximo 50 caracteres. Actual: ${value.length}`;
            } else {
                small.style.color = '#6c757d';
                small.textContent = 'Máximo 50 caracteres. Este nombre aparecerá en el listado de productos.';
            }
        });

        ubicacionInput.addEventListener('input', function() {
            const value = this.value.trim();
            const small = this.parentNode.nextElementSibling;
            
            if (value.length === 0) {
                small.style.color = '#e74c3c';
                small.textContent = 'La ubicación es obligatoria.';
            } else if (value.length > 150) {
                small.style.color = '#e74c3c';
                small.textContent = `Máximo 150 caracteres. Actual: ${value.length}`;
            } else {
                small.style.color = '#6c757d';
                small.textContent = 'Describe dónde se encuentran físicamente los productos de esta categoría.';
            }
        });

        // Prevenir pérdida de datos
        window.addEventListener('beforeunload', function(e) {
            if (!saveBtn.disabled) {
                e.preventDefault();
                e.returnValue = '¿Estás seguro de que quieres salir? Tienes cambios sin guardar.';
                return e.returnValue;
            }
        });

        // Confirmar al hacer clic en cancelar si hay cambios
        document.querySelector('.btn-secondary').addEventListener('click', function(e) {
            if (!saveBtn.disabled) {
                if (!confirm('¿Estás seguro de que quieres cancelar? Se perderán los cambios no guardados.')) {
                    e.preventDefault();
                }
            }
        });

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl+S para guardar
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                if (!saveBtn.disabled) {
                    document.getElementById('editForm').submit();
                }
            }
            
            // Escape para cancelar
            if (e.key === 'Escape') {
                document.querySelector('.btn-secondary').click();
            }
        });

        // Auto-focus en el primer campo
        nombreInput.focus();
        nombreInput.setSelectionRange(nombreInput.value.length, nombreInput.value.length);
    </script>
</body>
</html>