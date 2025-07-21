<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Verificación de autenticación

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
    <title>Nuevo Usuario - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- CSS de estilos generales para formularios -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de nuevo usuario */
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
        
        /* Grupos de formulario doble columna */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        
        /* Indicadores de fuerza de contraseña */
        .password-strength {
            margin-top: 8px;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background-color: #e74c3c;
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background-color: #f39c12;
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background-color: #27ae60;
        }
        
        /* Validación visual */
        .grupo-formulario.has-error input {
            border-color: #e74c3c;
        }
        
        .grupo-formulario.has-success input {
            border-color: #27ae60;
        }
        
        .validation-message {
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }
        
        .validation-message.error {
            color: #e74c3c;
            display: block;
        }
        
        .validation-message.success {
            color: #27ae60;
            display: block;
        }
        
        /* Tarjeta de información */
        .info-card {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .info-card h4 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-card ul {
            margin: 0;
            padding-left: 20px;
            color: #0d47a1;
        }
        
        .info-card li {
            margin-bottom: 5px;
            font-size: 14px;
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
        
        /* Toggle para mostrar/ocultar contraseña */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            font-size: 18px;
            user-select: none;
            background: none;
            border: none;
            padding: 5px;
        }
        
        .password-toggle:hover {
            color: #495057;
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
            
            .form-row {
                grid-template-columns: 1fr;
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
                <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">
                    <span style="margin-right: 10px;">👤</span>Nuevo Usuario
                </h1>
                <p style="color: #6c757d; font-size: 16px;">Crea una nueva cuenta de usuario para el sistema</p>
            </div>

            <!-- Contenido principal -->
            <div class="usuarios-content">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mensaje error" style="max-width: 700px; margin: 0 auto 20px;">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Tarjeta informativa -->
                <div class="info-card" style="max-width: 700px; margin: 0 auto 25px;">
                    <h4>📌 Información importante sobre los usuarios</h4>
                    <ul>
                        <li>El nombre de usuario debe ser único en el sistema</li>
                        <li>La contraseña debe tener al menos 6 caracteres con letras y números</li>
                        <li>El correo electrónico se usará para recuperación de contraseña (función futura)</li>
                        <li>Los usuarios podrán gestionar productos y categorías según sus permisos</li>
                    </ul>
                </div>

                <div class="formulario-card">
                    <form action="../../controllers/usuario_guardar.php" method="POST" id="formNuevoUsuario">
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label for="nombre">
                                    <span style="margin-right: 5px;">👤</span>Nombre:
                                </label>
                                <div class="input-group">
                                    <span class="input-icon"></span>
                                    <input type="text" 
                                           id="nombre" 
                                           name="nombre" 
                                           maxlength="40" 
                                           required
                                           placeholder="Ej: Juan"
                                           autocomplete="given-name">
                                </div>
                                <div class="validation-message" id="nombre-validation"></div>
                            </div>
                            
                            <div class="grupo-formulario">
                                <label for="apellido">
                                    <span style="margin-right: 5px;">👤</span>Apellido:
                                </label>
                                <div class="input-group">
                                    <span class="input-icon"></span>
                                    <input type="text" 
                                           id="apellido" 
                                           name="apellido" 
                                           maxlength="40" 
                                           required
                                           placeholder="Ej: Pérez"
                                           autocomplete="family-name">
                                </div>
                                <div class="validation-message" id="apellido-validation"></div>
                            </div>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="usuario">
                                <span style="margin-right: 5px;">🔑</span>Nombre de Usuario:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="text" 
                                       id="usuario" 
                                       name="usuario" 
                                       maxlength="20" 
                                       required
                                       placeholder="Ej: jperez"
                                       autocomplete="username">
                            </div>
                            <small>El nombre de usuario debe ser único y tener entre 4 y 20 caracteres.</small>
                            <div class="validation-message" id="usuario-validation"></div>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="email">
                                <span style="margin-right: 5px;">📧</span>Correo Electrónico:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       maxlength="70" 
                                       required
                                       placeholder="usuario@ejemplo.com"
                                       autocomplete="email">
                            </div>
                            <small>Asegúrate de usar un correo válido y activo.</small>
                            <div class="validation-message" id="email-validation"></div>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="clave">
                                <span style="margin-right: 5px;">🔒</span>Contraseña:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="password" 
                                       id="clave" 
                                       name="clave" 
                                       maxlength="200" 
                                       required
                                       placeholder="••••••••"
                                       autocomplete="new-password">
                                <button type="button" class="password-toggle" onclick="togglePassword('clave')">
                                    👁️
                                </button>
                            </div>
                            <small>La contraseña debe tener al menos 6 caracteres y contener letras y números.</small>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="password-strength"></div>
                            </div>
                            <div class="validation-message" id="clave-validation"></div>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label for="confirmar_clave">
                                <span style="margin-right: 5px;">🔒</span>Confirmar Contraseña:
                            </label>
                            <div class="input-group">
                                <span class="input-icon"></span>
                                <input type="password" 
                                       id="confirmar_clave" 
                                       name="confirmar_clave" 
                                       maxlength="200" 
                                       required
                                       placeholder="••••••••"
                                       autocomplete="new-password">
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmar_clave')">
                                    👁️
                                </button>
                            </div>
                            <small>Repita la contraseña para confirmar.</small>
                            <div class="validation-message" id="confirmar-validation"></div>
                        </div>
                        
                        <div class="acciones-formulario">
                            <a href="usuario_listar.php" class="btn btn-secondary">
                                <span style="margin-right: 5px;">←</span> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" id="btnGuardar">
                                <span style="margin-right: 5px;">💾</span> Guardar Usuario
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Información adicional -->
                <div style="max-width: 700px; margin: 25px auto 0; text-align: center;">
                    <p style="color: #6c757d; font-size: 14px;">
                        Una vez creado el usuario, podrá iniciar sesión con las credenciales proporcionadas.
                        Los permisos se pueden configurar después de la creación.
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

        // Función para mostrar/ocultar contraseña
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = '🙈';
            } else {
                field.type = 'password';
                button.textContent = '👁️';
            }
        }

        // Validación en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formNuevoUsuario');
            
            // Validación del nombre
            document.getElementById('nombre').addEventListener('input', function() {
                const value = this.value.trim();
                const validation = document.getElementById('nombre-validation');
                const grupo = this.closest('.grupo-formulario');
                
                if (value.length < 2) {
                    validation.textContent = 'El nombre debe tener al menos 2 caracteres';
                    validation.className = 'validation-message error';
                    grupo.classList.add('has-error');
                    grupo.classList.remove('has-success');
                } else {
                    validation.textContent = '✓ Nombre válido';
                    validation.className = 'validation-message success';
                    grupo.classList.add('has-success');
                    grupo.classList.remove('has-error');
                }
            });

            // Validación del apellido
            document.getElementById('apellido').addEventListener('input', function() {
                const value = this.value.trim();
                const validation = document.getElementById('apellido-validation');
                const grupo = this.closest('.grupo-formulario');
                
                if (value.length < 2) {
                    validation.textContent = 'El apellido debe tener al menos 2 caracteres';
                    validation.className = 'validation-message error';
                    grupo.classList.add('has-error');
                    grupo.classList.remove('has-success');
                } else {
                    validation.textContent = '✓ Apellido válido';
                    validation.className = 'validation-message success';
                    grupo.classList.add('has-success');
                    grupo.classList.remove('has-error');
                }
            });

            // Validación del nombre de usuario
            document.getElementById('usuario').addEventListener('input', function() {
                const value = this.value.trim();
                const validation = document.getElementById('usuario-validation');
                const grupo = this.closest('.grupo-formulario');
                
                if (value.length < 4) {
                    validation.textContent = 'El nombre de usuario debe tener al menos 4 caracteres';
                    validation.className = 'validation-message error';
                    grupo.classList.add('has-error');
                    grupo.classList.remove('has-success');
                } else if (value.length > 20) {
                    validation.textContent = 'El nombre de usuario no puede exceder 20 caracteres';
                    validation.className = 'validation-message error';
                    grupo.classList.add('has-error');
                    grupo.classList.remove('has-success');
                } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                    validation.textContent = 'Solo se permiten letras, números y guión bajo';
                    validation.className = 'validation-message error';
                    grupo.classList.add('has-error');
                    grupo.classList.remove('has-success');
                } else {
                    validation.textContent = '✓ Nombre de usuario válido';
                    validation.className = 'validation-message success';
                    grupo.classList.add('has-success');
                    grupo.classList.remove('has-error');
                }
            });

            // Validación del email
            document.getElementById('email').addEventListener('input', function() {
                const value = this.value.trim();
                const validation = document.getElementById('email-validation');
                const grupo = this.closest('.grupo-formulario');
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (!emailRegex.test(value)) {
                    validation.textContent = 'Ingresa un correo electrónico válido';
                    validation.className = 'validation-message error';
                    grupo.classList.add('has-error');
                    grupo.classList.remove('has-success');
                } else {
                    validation.textContent = '✓ Correo electrónico válido';
                    validation.className = 'validation-message success';
                    grupo.classList.add('has-success');
                    grupo.classList.remove('has-error');
                }
            });

            // Validación y medidor de fuerza de la contraseña
            document.getElementById('clave').addEventListener('input', function() {
                const value = this.value;
                const validation = document.getElementById('clave-validation');
                const grupo = this.closest('.grupo-formulario');
                const strengthBar = document.getElementById('password-strength');
                
                // Validación básica
                if (value.length < 6) {
                    validation.textContent = 'La contraseña debe tener al menos 6 caracteres';
                    validation.className = 'validation-message error';
                    grupo.classList.add('has-error');
                    grupo.classList.remove('has-success');
                } else if (!/[A-Za-z]/.test(value) || !/[0-9]/.test(value)) {
                    validation.textContent = 'La contraseña debe contener letras y números';
                    validation.className = 'validation-message error';
                    grupo.classList.add('has-error');
                    grupo.classList.remove('has-success');
                } else {
                    validation.textContent = '✓ Contraseña válida';
                    validation.className = 'validation-message success';
                    grupo.classList.add('has-success');
                    grupo.classList.remove('has-error');
                }
                
                // Medidor de fuerza
                let strength = 0;
                if (value.length >= 6) strength++;
                if (value.length >= 8) strength++;
                if (/[A-Z]/.test(value) && /[a-z]/.test(value)) strength++;
                if (/[0-9]/.test(value)) strength++;
                if (/[^A-Za-z0-9]/.test(value)) strength++;
                
                strengthBar.className = 'password-strength-bar';
                if (strength <= 2) {
                    strengthBar.classList.add('weak');
                } else if (strength <= 3) {
                    strengthBar.classList.add('medium');
                } else {
                    strengthBar.classList.add('strong');
                }
                
                // Revalidar confirmación si ya tiene valor
                const confirmar = document.getElementById('confirmar_clave');
                if (confirmar.value) {
                    confirmar.dispatchEvent(new Event('input'));
                }
            });

            // Validación de confirmación de contraseña
            document.getElementById('confirmar_clave').addEventListener('input', function() {
                const value = this.value;
                const clave = document.getElementById('clave').value;
                const validation = document.getElementById('confirmar-validation');
                const grupo = this.closest('.grupo-formulario');
                
                if (value !== clave) {
                    validation.textContent = 'Las contraseñas no coinciden';
                    validation.className = 'validation-message error';
                    grupo.classList.add('has-error');
                    grupo.classList.remove('has-success');
                } else if (value.length > 0) {
                    validation.textContent = '✓ Las contraseñas coinciden';
                    validation.className = 'validation-message success';
                    grupo.classList.add('has-success');
                    grupo.classList.remove('has-error');
                }
            });

            // Validación antes de enviar
            form.addEventListener('submit', function(e) {
                const nombre = document.getElementById('nombre').value.trim();
                const apellido = document.getElementById('apellido').value.trim();
                const usuario = document.getElementById('usuario').value.trim();
                const email = document.getElementById('email').value.trim();
                const clave = document.getElementById('clave').value;
                const confirmar = document.getElementById('confirmar_clave').value;
                
                let hasErrors = false;
                
                // Validar todos los campos
                if (nombre.length < 2 || apellido.length < 2) {
                    hasErrors = true;
                }
                
                if (usuario.length < 4 || usuario.length > 20) {
                    hasErrors = true;
                }
                
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    hasErrors = true;
                }
                
                if (clave.length < 6 || !/[A-Za-z]/.test(clave) || !/[0-9]/.test(clave)) {
                    hasErrors = true;
                }
                
                if (clave !== confirmar) {
                    hasErrors = true;
                }
                
                if (hasErrors) {
                    e.preventDefault();
                    alert('Por favor, corrija los errores en el formulario antes de continuar.');
                }
            });
        });

        // Auto-focus en el primer campo
        document.getElementById('nombre').focus();
    </script>
</body>
</html>