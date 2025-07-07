<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Nueva Categoría - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales para formularios -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de nueva categoría */
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
            max-width: 600px;
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
        
        /* Tarjeta de ayuda */
        .help-card {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .help-card h4 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .help-card p {
            color: #6c757d;
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .help-examples {
            margin-top: 10px;
        }
        
        .help-examples span {
            display: inline-block;
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
            margin-right: 8px;
            margin-bottom: 5px;
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
                    <span style="margin-right: 10px;">📁</span>Nueva Categoría
                </h1>
                <p style="color: #6c757d; font-size: 16px;">Agrega una nueva categoría para organizar tus productos</p>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mensaje error" style="max-width: 600px; margin: 0 auto 20px;">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Tarjeta de ayuda -->
                <div class="help-card" style="max-width: 600px; margin: 0 auto 25px;">
                    <h4>💡 Consejos para crear categorías</h4>
                    <p>Las categorías te ayudan a organizar y encontrar productos más fácilmente. Elige nombres descriptivos y ubicaciones específicas.</p>
                    <div class="help-examples">
                        <strong>Ejemplos de nombres:</strong>
                        <span>Electrónicos</span>
                        <span>Ropa</span>
                        <span>Hogar</span>
                        <span>Deportes</span>
                    </div>
                    <div class="help-examples">
                        <strong>Ejemplos de ubicaciones:</strong>
                        <span>Pasillo A, Estante 1</span>
                        <span>Almacén Principal</span>
                        <span>Sala de exhibición</span>
                    </div>
                </div>

                <div class="formulario-card">
                    <form action="../../controllers/categoria_guardar.php" method="POST">
                        <div class="grupo-formulario">
                            <label for="nombre">
                                <span style="margin-right: 5px;">🏷️</span>Nombre de la Categoría:
                            </label>
                            <div class="input-group">
                                <span class="input-icon">📝</span>
                                <input type="text" 
                                       id="nombre" 
                                       name="nombre" 
                                       maxlength="50" 
                                       required
                                       placeholder="Ej: Electrónicos, Ropa, Hogar..."
                                       value="<?php echo isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : ''; ?>">
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
                                       placeholder="Ej: Pasillo 3, Estante B-2"
                                       value="<?php echo isset($_GET['ubicacion']) ? htmlspecialchars($_GET['ubicacion']) : ''; ?>">
                            </div>
                            <small>Describe dónde se encuentran físicamente los productos de esta categoría en tu almacén o tienda.</small>
                        </div>
                        
                        <div class="acciones-formulario">
                            <a href="categoria_listar.php" class="btn btn-secondary">
                                <span style="margin-right: 5px;">←</span> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <span style="margin-right: 5px;">💾</span> Guardar Categoría
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Información adicional -->
                <div style="max-width: 600px; margin: 25px auto 0; text-align: center;">
                    <p style="color: #6c757d; font-size: 14px;">
                        Una vez creada la categoría, podrás asignar productos a ella desde el 
                        <a href="../productos/producto_nuevo.php" style="color: #2980b9; text-decoration: none;">formulario de productos</a>.
                    </p>
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

        // Validación en tiempo real
        document.getElementById('nombre').addEventListener('input', function() {
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

        document.getElementById('ubicacion').addEventListener('input', function() {
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
                small.textContent = 'Describe dónde se encuentran físicamente los productos de esta categoría en tu almacén o tienda.';
            }
        });

        // Auto-focus en el primer campo
        document.getElementById('nombre').focus();
    </script>
</body>
</html>