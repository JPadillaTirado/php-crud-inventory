<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}

// Consultar las categorías para el select
$sql_categorias = "SELECT categoria_id, categoria_nombre FROM categoria ORDER BY categoria_nombre ASC";
$resultado_categorias = $conexion->query($sql_categorias);

// Consultar usuarios para el select
$sql_usuarios = "SELECT usuario_id, usuario_nombre, usuario_apellido FROM usuario ORDER BY usuario_nombre ASC";
$resultado_usuarios = $conexion->query($sql_usuarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Nuevo Producto - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales para formularios -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de nuevo producto */
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
        
        .formulario-card {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            max-width: 800px;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .productos-content {
                padding: 20px;
            }
            
            .formulario-card {
                padding: 20px;
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
            <div class="productos-header">
                <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">Nuevo Producto</h1>
                <p style="color: #6c757d; font-size: 16px;">Agrega un nuevo producto al inventario</p>
            </div>

            <!-- Contenido principal -->
            <div class="productos-content">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mensaje error" style="max-width: 800px; margin: 0 auto 20px;">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="formulario-card">
                    <form action="../../controllers/producto_guarda.php" method="POST" enctype="multipart/form-data">
                        <div class="grupo-formulario">
                            <label for="codigo">Código del Producto:</label>
                            <input type="text" id="codigo" name="codigo" maxlength="70" required 
                                   placeholder="Ej: PROD-001">
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="nombre">Nombre del Producto:</label>
                            <input type="text" id="nombre" name="nombre" maxlength="70" required
                                   placeholder="Ej: Laptop Dell Inspiron">
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="precio">Precio ($):</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" required
                                   placeholder="0.00">
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="stock">Stock (unidades):</label>
                            <input type="number" id="stock" name="stock" min="0" required
                                   placeholder="0">
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="categoria">Categoría:</label>
                            <select id="categoria" name="categoria" required>
                                <option value="">Seleccione una categoría</option>
                                <?php 
                                if ($resultado_categorias && $resultado_categorias->num_rows > 0) {
                                    while ($categoria = $resultado_categorias->fetch_assoc()) {
                                        echo '<option value="' . $categoria['categoria_id'] . '">' . 
                                             htmlspecialchars($categoria['categoria_nombre']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No hay categorías disponibles</option>';
                                }
                                ?>
                            </select>
                            <small><a href="../categorias/categoria_nuevo.php">Agregar nueva categoría</a></small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="usuario">Usuario responsable:</label>
                            <select id="usuario" name="usuario" required>
                                <option value="">Seleccione un usuario</option>
                                <?php 
                                if ($resultado_usuarios && $resultado_usuarios->num_rows > 0) {
                                    while ($usuario = $resultado_usuarios->fetch_assoc()) {
                                        echo '<option value="' . $usuario['usuario_id'] . '">' . 
                                             htmlspecialchars($usuario['usuario_nombre'] . ' ' . $usuario['usuario_apellido']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No hay usuarios disponibles</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="foto">Imagen del Producto:</label>
                            <input type="file" id="foto" name="foto" accept="image/*" 
                                   onchange="previewImage(this)">
                            <small>Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
                            <div id="imagePreview" style="margin-top: 10px; display: none;">
                                <img id="preview" src="" alt="Vista previa" style="max-width: 200px; max-height: 200px;">
                            </div>
                        </div>
                        
                        <div class="acciones-formulario">
                            <a href="producto_listar.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar Producto</button>
                        </div>
                    </form>
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

        // Función para previsualizar la imagen
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const previewContainer = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                previewContainer.style.display = 'none';
            }
        }
    </script>
</body>
</html>