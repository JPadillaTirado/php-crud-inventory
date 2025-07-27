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
    header('Location: proveedor_listar.php?mensaje=ID de proveedor no válido&tipo=error');
    exit;
}

$proveedor_id = intval($_GET['id']);

// Consultar datos del proveedor
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

// Obtener datos del proveedor
$proveedor = $resultado->fetch_assoc();

// Obtener estadísticas del proveedor
$sql_compras = "SELECT COUNT(*) as total_compras FROM compra WHERE proveedor_id = ?";
$stmt_compras = $conexion->prepare($sql_compras);
$stmt_compras->bind_param("i", $proveedor_id);
$stmt_compras->execute();
$resultado_compras = $stmt_compras->get_result();
$total_compras = $resultado_compras->fetch_assoc()['total_compras'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Editar Proveedor - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales para formularios -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de editar proveedor */
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
            max-width: 700px;
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
        
        .grupo-formulario input, .grupo-formulario select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .grupo-formulario input:focus, .grupo-formulario select:focus {
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
        
        /* Input groups con iconos */
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 16px;
            z-index: 1;
        }
        
        .input-group input {
            padding-left: 45px;
        }
        
        /* Tarjeta de información */
        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .info-card h4 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .info-card p {
            margin: 0 0 15px 0;
            opacity: 0.9;
        }
        
        .info-stats {
            display: flex;
            gap: 30px;
        }
        
        .info-stat {
            text-align: center;
        }
        
        .info-stat .number {
            display: block;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .info-stat .label {
            font-size: 14px;
            opacity: 0.8;
        }
        
        /* Badge para ID */
        .provider-id-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 14px;
            margin-left: 15px;
        }
        
        /* Botones */
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
        
        /* Estados de cambio */
        .field-changed {
            border-color: #f39c12 !important;
            background-color: #fef9e7;
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
            
            .info-stats {
                gap: 15px;
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
                    <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">
                        <span style="margin-right: 10px;">✏️</span>Editar Proveedor
                        <span class="provider-id-badge">ID: <?php echo $proveedor_id; ?></span>
                    </h1>
                    <p style="color: #6c757d; font-size: 16px;">Modifica la información del proveedor</p>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="categorias-content">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mensaje error" style="max-width: 700px; margin: 0 auto 20px;">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Tarjeta de información del proveedor -->
                <div class="info-card" style="max-width: 700px; margin: 0 auto 25px;">
                    <div class="info-card-content">
                        <h4>📊 Información Actual del Proveedor</h4>
                        <p>Este proveedor está registrado en el sistema y puede ser modificado desde este formulario.</p>
                        <div class="info-stats">
                            <div class="info-stat">
                                <span class="number"><?php echo $total_compras; ?></span>
                                <span class="label">Compras realizadas</span>
                            </div>
                            <div class="info-stat">
                                <span class="number"><?php echo $proveedor_id; ?></span>
                                <span class="label">ID único</span>
                            </div>
                            <div class="info-stat">
                                <span class="number"><?php echo $proveedor['proveedor_estado'] ? 'Activo' : 'Inactivo'; ?></span>
                                <span class="label">Estado actual</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="formulario-card">
                    <form action="../../controllers/proveedor_actualizar.php" method="POST" id="editForm">
                        <input type="hidden" name="proveedor_id" value="<?php echo $proveedor_id; ?>">
                        
                        <!-- Información básica -->
                        <div class="grupo-formulario">
                            <label for="nombre" class="required">
                                <span style="margin-right: 5px;">🏢</span>Nombre de la Empresa:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="nombre" 
                                       name="nombre" 
                                       maxlength="100" 
                                       required
                                       value="<?php echo htmlspecialchars($proveedor['proveedor_nombre']); ?>"
                                       data-original="<?php echo htmlspecialchars($proveedor['proveedor_nombre']); ?>">
                            </div>
                            <small>Máximo 100 caracteres. Nombre completo de la empresa proveedora.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="rut_nit" class="required">
                                <span style="margin-right: 5px;">📄</span>RUT/NIT:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="rut_nit" 
                                       name="rut_nit" 
                                       maxlength="20" 
                                       required
                                       value="<?php echo htmlspecialchars($proveedor['proveedor_rut_nit']); ?>"
                                       data-original="<?php echo htmlspecialchars($proveedor['proveedor_rut_nit']); ?>">
                            </div>
                            <small>Número de identificación tributaria único del proveedor.</small>
                        </div>

                        <!-- Información de contacto -->
                        <div class="grupo-formulario">
                            <label for="contacto">
                                <span style="margin-right: 5px;">👤</span>Persona de Contacto:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="contacto" 
                                       name="contacto" 
                                       maxlength="100" 
                                       value="<?php echo htmlspecialchars($proveedor['proveedor_contacto']); ?>"
                                       data-original="<?php echo htmlspecialchars($proveedor['proveedor_contacto']); ?>">
                            </div>
                            <small>Nombre del contacto principal en la empresa.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="telefono">
                                <span style="margin-right: 5px;">📞</span>Teléfono:
                            </label>
                            <div class="input-group">
                                <span class="input-icon">📞</span>
                                <input type="tel" 
                                       id="telefono" 
                                       name="telefono" 
                                       maxlength="20" 
                                       value="<?php echo htmlspecialchars($proveedor['proveedor_telefono']); ?>"
                                       data-original="<?php echo htmlspecialchars($proveedor['proveedor_telefono']); ?>">
                            </div>
                            <small>Número de teléfono principal para contactar al proveedor.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="email">
                                <span style="margin-right: 5px;">✉️</span>Email:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       maxlength="70" 
                                       value="<?php echo htmlspecialchars($proveedor['proveedor_email']); ?>"
                                       data-original="<?php echo htmlspecialchars($proveedor['proveedor_email']); ?>">
                            </div>
                            <small>Correo electrónico para comunicaciones comerciales.</small>
                        </div>

                        <!-- Información de ubicación -->
                        <div class="grupo-formulario">
                            <label for="direccion">
                                <span style="margin-right: 5px;">📍</span>Dirección:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="direccion" 
                                       name="direccion" 
                                       maxlength="200" 
                                       value="<?php echo htmlspecialchars($proveedor['proveedor_direccion']); ?>"
                                       data-original="<?php echo htmlspecialchars($proveedor['proveedor_direccion']); ?>">
                            </div>
                            <small>Dirección física de la empresa.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="ciudad">
                                <span style="margin-right: 5px;">🏙️</span>Ciudad:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="ciudad" 
                                       name="ciudad" 
                                       maxlength="50" 
                                       value="<?php echo htmlspecialchars($proveedor['proveedor_ciudad']); ?>"
                                       data-original="<?php echo htmlspecialchars($proveedor['proveedor_ciudad']); ?>">
                            </div>
                            <small>Ciudad donde se encuentra ubicado el proveedor.</small>
                        </div>

                        <!-- Estado del proveedor -->
                        <div class="grupo-formulario">
                            <label for="estado">
                                <span style="margin-right: 5px;">⚡</span>Estado:
                            </label>
                            <select id="estado" name="estado" required data-original="<?php echo $proveedor['proveedor_estado']; ?>">
                                <option value="1" <?php echo ($proveedor['proveedor_estado'] == 1) ? 'selected' : ''; ?>>✅ Activo</option>
                                <option value="0" <?php echo ($proveedor['proveedor_estado'] == 0) ? 'selected' : ''; ?>>❌ Inactivo</option>
                            </select>
                            <small>Estado del proveedor en el sistema.</small>
                        </div>

                        <!-- Botones de acción -->
                        <div class="acciones-formulario">
                            <a href="proveedor_listar.php" class="btn btn-secondary">
                                <span style="margin-right: 8px;">←</span> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
                                <span style="margin-right: 8px;">💾</span> Actualizar Proveedor
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Información adicional -->
                <?php if ($total_compras > 0): ?>
                    <div style="max-width: 700px; margin: 25px auto 0; text-align: center;">
                        <p style="color: #6c757d; font-size: 14px;">
                            ⚠️ <strong>Nota:</strong> Este proveedor tiene <strong><?php echo $total_compras; ?> compra(s)</strong> registrada(s). 
                            Los cambios no afectarán las compras ya realizadas.
                        </p>
                    </div>
                <?php endif; ?>
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

        // Manejo del formulario de edición
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editForm');
            const saveBtn = document.getElementById('saveBtn');
            const inputs = form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], select');
            
            // Función para verificar si hay cambios
            function checkChanges() {
                let hasChanges = false;
                
                inputs.forEach(input => {
                    const original = input.dataset.original || '';
                    const current = input.value || '';
                    
                    if (original !== current) {
                        hasChanges = true;
                        input.classList.add('field-changed');
                    } else {
                        input.classList.remove('field-changed');
                    }
                });
                
                saveBtn.disabled = !hasChanges;
            }
            
            // Agregar event listeners a todos los inputs
            inputs.forEach(input => {
                input.addEventListener('input', checkChanges);
                input.addEventListener('change', checkChanges);
            });
            
            // Validaciones específicas
            document.getElementById('rut_nit').addEventListener('input', function(e) {
                // Remover espacios y caracteres especiales excepto guiones y puntos
                let value = e.target.value.replace(/[^\d\-\.]/g, '');
                e.target.value = value;
                checkChanges();
            });

            document.getElementById('telefono').addEventListener('input', function(e) {
                // Permitir solo números, espacios, guiones, paréntesis y el símbolo +
                let value = e.target.value.replace(/[^0-9\s\-\(\)\+]/g, '');
                e.target.value = value;
                checkChanges();
            });
            
            // Verificación inicial
            checkChanges();
        });
    </script>
</body>
</html>