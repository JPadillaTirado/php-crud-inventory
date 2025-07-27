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
    header('Location: usuario_listar.php?mensaje=ID de usuario no válido&tipo=error');
    exit;
}

$usuario_id = intval($_GET['id']);

// Consultar datos del usuario
$sql = "SELECT * FROM usuario WHERE usuario_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar si el usuario existe
if ($resultado->num_rows === 0) {
    header('Location: usuario_listar.php?mensaje=El usuario no existe&tipo=error');
    exit;
}

// Obtener datos del usuario
$usuario = $resultado->fetch_assoc();

// Obtener estadísticas del usuario
$sql_productos = "SELECT COUNT(*) as total_productos FROM producto WHERE usuario_id = ?";
$stmt_productos = $conexion->prepare($sql_productos);
$stmt_productos->bind_param("i", $usuario_id);
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
    <title>Editar Usuario - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales para formularios -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de editar usuario */
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
        }
        
        .usuarios-content {
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
            display: flex;
            align-items: center;
            gap: 8px;
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
        
        /* Badge para ID */
        .user-id-badge {
            display: inline-block;
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        /* Tarjeta de información del usuario */
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
        
        /* Sección de contraseña */
        .password-section {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .password-section h4 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .password-section small {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: block;
            font-size: 13px;
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
        
        /* Validación visual */
        .input-valid {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1) !important;
        }
        
        .input-invalid {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
        }
        
        .validation-message {
            margin-top: 5px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .validation-message.valid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .validation-message.invalid {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Avatar del usuario */
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin: 0 auto 20px;
            position: relative;
        }
        
        .user-avatar::after {
            content: '';
            position: absolute;
            inset: 3px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .user-avatar span {
            position: relative;
            z-index: 2;
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
        
        /* Botones */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background-color: #2980b9;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1f618d;
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .usuarios-content {
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
                justify-content: center;
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
            
            .user-avatar {
                width: 60px;
                height: 60px;
                font-size: 24px;
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
                <a href="usuario_listar.php" class="nav-item active" title="Usuarios">
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
                <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">
                    <span style="margin-right: 10px;">✏️</span>Editar Usuario
                    <span class="user-id-badge">ID: <?php echo $usuario_id; ?></span>
                </h1>
                <p style="color: #6c757d; font-size: 16px;">Modifica la información del usuario</p>
            </div>

            <!-- Contenido principal -->
            <div class="usuarios-content">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mensaje error" style="max-width: 800px; margin: 0 auto 20px;">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Tarjeta de información del usuario -->
                <div class="info-card" style="max-width: 800px; margin: 0 auto 25px;">
                    <div class="info-card-content">
                        <!-- Avatar del usuario -->
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($usuario['usuario_nombre'], 0, 1) . substr($usuario['usuario_apellido'], 0, 1)); ?></span>
                        </div>
                        
                        <h4>👤 Información Actual del Usuario</h4>
                        <p>Editando el perfil de <strong><?php echo htmlspecialchars($usuario['usuario_nombre'] . ' ' . $usuario['usuario_apellido']); ?></strong></p>
                        <div class="info-stats">
                            <div class="info-stat">
                                <span class="number"><?php echo $total_productos; ?></span>
                                <span class="label">Productos asociados</span>
                            </div>
                            <div class="info-stat">
                                <span class="number"><?php echo $usuario_id; ?></span>
                                <span class="label">ID único</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="formulario-card">
                    <form action="../../controllers/usuario_actualizar.php" method="POST" id="editForm">
                        <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">
                        
                        <div class="grupo-formulario">
                            <label for="nombre">
                                <span>👤</span> Nombre:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="nombre" 
                                       name="nombre" 
                                       maxlength="40" 
                                       required
                                       value="<?php echo htmlspecialchars($usuario['usuario_nombre']); ?>"
                                       data-original="<?php echo htmlspecialchars($usuario['usuario_nombre']); ?>">
                            </div>
                            <small>Máximo 40 caracteres.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="apellido">
                                <span>👤</span> Apellido:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="apellido" 
                                       name="apellido" 
                                       maxlength="40" 
                                       required
                                       value="<?php echo htmlspecialchars($usuario['usuario_apellido']); ?>"
                                       data-original="<?php echo htmlspecialchars($usuario['usuario_apellido']); ?>">
                            </div>
                            <small>Máximo 40 caracteres.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="usuario">
                                <span>🔑</span> Nombre de Usuario:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="usuario" 
                                       name="usuario" 
                                       maxlength="20" 
                                       required
                                       value="<?php echo htmlspecialchars($usuario['usuario_usuario']); ?>"
                                       data-original="<?php echo htmlspecialchars($usuario['usuario_usuario']); ?>">
                            </div>
                            <small>El nombre de usuario debe ser único y tener entre 4 y 20 caracteres.</small>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="email">
                                <span>📧</span> Correo Electrónico:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       maxlength="70" 
                                       required
                                       value="<?php echo htmlspecialchars($usuario['usuario_email']); ?>"
                                       data-original="<?php echo htmlspecialchars($usuario['usuario_email']); ?>">
                            </div>
                            <small>Ejemplo: usuario@ejemplo.com</small>
                        </div>
                        
                        <!-- Sección especial para cambio de contraseña -->
                        <div class="password-section">
                            <h4>
                                <span>🔒</span> Cambiar Contraseña (Opcional)
                            </h4>
                            <small>Solo complete estos campos si desea cambiar la contraseña actual.</small>
                            
                            <div class="grupo-formulario">
                                <label for="clave">
                                    <span>🔒</span> Nueva Contraseña:
                                </label>
                                <div class="input-group">
                                    <span class="input-icon"></span>
                                    <input type="password" 
                                           id="clave" 
                                           name="clave" 
                                           maxlength="200"
                                           placeholder="Deje en blanco para mantener la actual">
                                </div>
                                <small>La nueva contraseña debe tener al menos 6 caracteres y contener letras y números.</small>
                            </div>
                            
                            <div class="grupo-formulario">
                                <label for="confirmar_clave">
                                    <span>🔒</span> Confirmar Nueva Contraseña:
                                </label>
                                <div class="input-group">
                                    <span class="input-icon"></span>
                                    <input type="password" 
                                           id="confirmar_clave" 
                                           name="confirmar_clave" 
                                           maxlength="200"
                                           placeholder="Repita la nueva contraseña">
                                </div>
                                <small>Debe coincidir con la nueva contraseña.</small>
                            </div>
                        </div>
                        
                        <div class="acciones-formulario">
                            <a href="usuario_listar.php" class="btn btn-secondary">
                                <span>←</span> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
                                <span>💾</span> Actualizar Usuario
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Información adicional -->
                <?php if ($total_productos > 0): ?>
                    <div style="max-width: 800px; margin: 25px auto 0; text-align: center;">
                        <p style="color: #6c757d; font-size: 14px;">
                            ℹ️ <strong>Nota:</strong> Este usuario tiene <strong><?php echo $total_productos; ?> producto(s)</strong> asociado(s). 
                            Los cambios en el nombre del usuario se reflejarán en los productos.
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
        const apellidoInput = document.getElementById('apellido');
        const usuarioInput = document.getElementById('usuario');
        const emailInput = document.getElementById('email');
        const claveInput = document.getElementById('clave');
        const confirmarClaveInput = document.getElementById('confirmar_clave');
        const saveBtn = document.getElementById('saveBtn');
        const changesIndicator = document.getElementById('changesIndicator');
        
        function checkForChanges() {
            const nombreChanged = nombreInput.value !== nombreInput.dataset.original;
            const apellidoChanged = apellidoInput.value !== apellidoInput.dataset.original;
            const usuarioChanged = usuarioInput.value !== usuarioInput.dataset.original;
            const emailChanged = emailInput.value !== emailInput.dataset.original;
            const passwordChanged = claveInput.value.length > 0;
            
            const hasChanges = nombreChanged || apellidoChanged || usuarioChanged || emailChanged || passwordChanged;
            
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
        apellidoInput.addEventListener('input', checkForChanges);
        usuarioInput.addEventListener('input', checkForChanges);
        emailInput.addEventListener('input', checkForChanges);
        claveInput.addEventListener('input', checkForChanges);
        confirmarClaveInput.addEventListener('input', checkForChanges);
        
        // Validación de contraseñas
        function validatePasswords() {
            const password = claveInput.value;
            const confirmPassword = confirmarClaveInput.value;
            
            if (password.length > 0 || confirmPassword.length > 0) {
                if (password !== confirmPassword) {
                    confirmarClaveInput.classList.add('input-invalid');
                    confirmarClaveInput.classList.remove('input-valid');
                    
                    let msg = confirmarClaveInput.nextElementSibling?.nextElementSibling;
                    if (!msg || !msg.classList.contains('validation-message')) {
                        msg = document.createElement('div');
                        msg.className = 'validation-message invalid';
                        confirmarClaveInput.parentNode.parentNode.appendChild(msg);
                    }
                    msg.textContent = '❌ Las contraseñas no coinciden';
                    msg.className = 'validation-message invalid';
                } else if (password.length >= 6) {
                    confirmarClaveInput.classList.add('input-valid');
                    confirmarClaveInput.classList.remove('input-invalid');
                    
                    let msg = confirmarClaveInput.nextElementSibling?.nextElementSibling;
                    if (!msg || !msg.classList.contains('validation-message')) {
                        msg = document.createElement('div');
                        msg.className = 'validation-message valid';
                        confirmarClaveInput.parentNode.parentNode.appendChild(msg);
                    }
                    msg.textContent = '✅ Las contraseñas coinciden';
                    msg.className = 'validation-message valid';
                }
                
                // Validar longitud de contraseña
                if (password.length > 0 && password.length < 6) {
                    claveInput.classList.add('input-invalid');
                    claveInput.classList.remove('input-valid');
                } else if (password.length >= 6) {
                    claveInput.classList.add('input-valid');
                    claveInput.classList.remove('input-invalid');
                }
            } else {
                // Limpiar validaciones si no hay contraseña
                claveInput.classList.remove('input-valid', 'input-invalid');
                confirmarClaveInput.classList.remove('input-valid', 'input-invalid');
                
                const msg = confirmarClaveInput.parentNode.parentNode.querySelector('.validation-message');
                if (msg) {
                    msg.remove();
                }
            }
        }
        
        claveInput.addEventListener('input', validatePasswords);
        confirmarClaveInput.addEventListener('input', validatePasswords);
        
        // Validación en tiempo real para otros campos
        usuarioInput.addEventListener('input', function() {
            const value = this.value.trim();
            const small = this.parentNode.nextElementSibling;
            
            if (value.length === 0) {
                small.style.color = '#e74c3c';
                small.textContent = 'El nombre de usuario es obligatorio.';
                this.classList.add('input-invalid');
                this.classList.remove('input-valid');
            } else if (value.length < 4) {
                small.style.color = '#e74c3c';
                small.textContent = 'El nombre de usuario debe tener al menos 4 caracteres.';
                this.classList.add('input-invalid');
                this.classList.remove('input-valid');
            } else if (value.length > 20) {
                small.style.color = '#e74c3c';
                small.textContent = `Máximo 20 caracteres. Actual: ${value.length}`;
                this.classList.add('input-invalid');
                this.classList.remove('input-valid');
            } else {
                small.style.color = '#6c757d';
                small.textContent = 'El nombre de usuario debe ser único y tener entre 4 y 20 caracteres.';
                this.classList.add('input-valid');
                this.classList.remove('input-invalid');
            }
        });

        emailInput.addEventListener('input', function() {
            const value = this.value.trim();
            const small = this.parentNode.nextElementSibling;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value.length === 0) {
                small.style.color = '#e74c3c';
                small.textContent = 'El correo electrónico es obligatorio.';
                this.classList.add('input-invalid');
                this.classList.remove('input-valid');
            } else if (!emailRegex.test(value)) {
                small.style.color = '#e74c3c';
                small.textContent = 'Por favor, ingrese un correo electrónico válido.';
                this.classList.add('input-invalid');
                this.classList.remove('input-valid');
            } else {
                small.style.color = '#6c757d';
                small.textContent = 'Ejemplo: usuario@ejemplo.com';
                this.classList.add('input-valid');
                this.classList.remove('input-invalid');
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