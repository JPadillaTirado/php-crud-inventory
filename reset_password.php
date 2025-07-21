<?php
/**
 * PÁGINA DE RESTABLECIMIENTO DE CONTRASEÑA
 * 
 * Procesa el token enviado por correo y permite al usuario
 * establecer una nueva contraseña
 */

session_start();

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/conexion.php';
require_once 'config/email_config.php';
require_once 'includes/password_reset_functions.php';
require_once 'includes/EmailSender.php';

$token = $_GET['token'] ?? '';
$mensaje = '';
$tipo_mensaje = '';
$token_valido = false;
$usuario_data = null;
$password_cambiada = false;

// Validar token
if (!empty($token)) {
    $usuario_data = isTokenValid($token);
    if ($usuario_data) {
        $token_valido = true;
    } else {
        $mensaje = 'El enlace de recuperación no es válido o ha expirado. Solicita un nuevo enlace.';
        $tipo_mensaje = 'error';
    }
} else {
    $mensaje = 'Enlace de recuperación inválido. Por favor, solicita un nuevo enlace.';
    $tipo_mensaje = 'error';
}

// Procesar formulario de nueva contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $nueva_password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirm_password'] ?? '';
    
    // Validaciones
    if (empty($nueva_password) || empty($confirmar_password)) {
        $mensaje = 'Por favor, completa todos los campos.';
        $tipo_mensaje = 'error';
    } elseif ($nueva_password !== $confirmar_password) {
        $mensaje = 'Las contraseñas no coinciden.';
        $tipo_mensaje = 'error';
    } else {
        // Validar fortaleza de la contraseña
        $validation = validatePasswordStrength($nueva_password);
        
        if (!$validation['valid']) {
            $mensaje = implode('<br>', $validation['errors']);
            $tipo_mensaje = 'error';
        } else {
            // Actualizar contraseña
            if (updateUserPassword($usuario_data['usuario_id'], $nueva_password)) {
                // Marcar token como usado
                markTokenAsUsed($token);
                
                // Invalidar otros tokens del usuario
                invalidateUserTokens($usuario_data['usuario_id']);
                
                // Enviar correo de confirmación (opcional)
                $nombreCompleto = $usuario_data['usuario_nombre'] . ' ' . $usuario_data['usuario_apellido'];
                EmailSender::sendPasswordChangeConfirmation($usuario_data['usuario_email'], $nombreCompleto);
                
                $password_cambiada = true;
                $mensaje = '¡Contraseña actualizada exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.';
                $tipo_mensaje = 'success';
                
                // Limpiar datos del token para seguridad
                $token_valido = false;
            } else {
                $mensaje = 'Error al actualizar la contraseña. Intenta nuevamente.';
                $tipo_mensaje = 'error';
            }
        }
    }
}

// Limpiar tokens expirados
cleanExpiredTokens();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Contraseña - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px 30px;
            z-index: 100;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* Container principal */
        .reset-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .reset-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 48px;
            width: 100%;
            max-width: 440px;
            border: 1px solid #e2e8f0;
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .reset-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
            color: white;
        }
        
        .reset-icon.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .reset-icon.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .reset-icon.default {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .reset-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .reset-subtitle {
            font-size: 16px;
            color: #64748b;
            font-weight: 400;
            line-height: 1.5;
        }
        
        /* Formulario */
        .reset-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 400;
            background-color: #ffffff;
            transition: all 0.2s ease;
            color: #1f2937;
            position: relative;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input::placeholder {
            color: #9ca3af;
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #6b7280;
            user-select: none;
            padding: 5px;
            border-radius: 4px;
            transition: color 0.2s ease;
        }
        
        .password-toggle:hover {
            color: #374151;
        }
        
        .password-strength {
            margin-top: 8px;
            height: 6px;
            background-color: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
            border-radius: 3px;
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background-color: #ef4444;
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background-color: #f59e0b;
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background-color: #10b981;
        }
        
        .password-feedback {
            margin-top: 8px;
            font-size: 12px;
            color: #6b7280;
        }
        
        .reset-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
        }
        
        .reset-button:hover:not(:disabled) {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .reset-button:active {
            transform: translateY(0);
        }
        
        .reset-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Mensajes */
        .message {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.4;
        }
        
        .message.success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .message.error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .message.info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #2563eb;
        }
        
        /* Links */
        .reset-links {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
            font-size: 14px;
        }
        
        .link:hover {
            color: #2563eb;
            text-decoration: underline;
        }
        
        /* User info card */
        .user-info {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .user-info h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .user-info p {
            font-size: 14px;
            color: #64748b;
        }
        
        /* Success state */
        .success-actions {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .success-button {
            padding: 12px 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s ease;
        }
        
        .success-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .header {
                padding: 16px 20px;
            }
            
            .reset-card {
                padding: 32px 24px;
                border-radius: 16px;
                margin: 0 16px;
            }
            
            .reset-title {
                font-size: 24px;
            }
        }
        
        /* Animaciones */
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
        
        .reset-card {
            animation: slideUp 0.6s ease-out;
        }
        
        .header {
            animation: slideUp 0.6s ease-out 0.2s both;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <div class="logo-icon">🔐</div>
            <div class="logo-text"><?php echo APP_NAME; ?></div>
        </div>
    </div>

    <!-- Container principal -->
    <div class="reset-container">
        <div class="reset-card">
            <?php if ($password_cambiada): ?>
                <!-- Estado de éxito -->
                <div class="reset-header">
                    <div class="reset-icon success">✅</div>
                    <h1 class="reset-title">¡Contraseña Actualizada!</h1>
                    <p class="reset-subtitle">
                        Tu contraseña ha sido cambiada exitosamente. 
                        Ya puedes iniciar sesión con tu nueva contraseña.
                    </p>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                    <div class="message success">
                        ✓ <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>
                
                <div class="success-actions">
                    <a href="login.php" class="success-button">
                        🔑 Iniciar Sesión Ahora
                    </a>
                    <a href="dashboard.php" class="link">Ir al Dashboard</a>
                </div>
                
            <?php elseif (!$token_valido): ?>
                <!-- Estado de error -->
                <div class="reset-header">
                    <div class="reset-icon error">❌</div>
                    <h1 class="reset-title">Enlace No Válido</h1>
                    <p class="reset-subtitle">
                        El enlace de recuperación no es válido o ha expirado.
                    </p>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                    <div class="message error">
                        ⚠️ <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>
                
                <div class="success-actions">
                    <a href="forgot_password.php" class="success-button" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                        🔄 Solicitar Nuevo Enlace
                    </a>
                    <a href="login.php" class="link">← Volver al Login</a>
                </div>
                
            <?php else: ?>
                <!-- Formulario de nueva contraseña -->
                <div class="reset-header">
                    <div class="reset-icon default">🔒</div>
                    <h1 class="reset-title">Crear Nueva Contraseña</h1>
                    <p class="reset-subtitle">
                        Ingresa tu nueva contraseña para completar el proceso de recuperación.
                    </p>
                </div>
                
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($usuario_data['usuario_nombre'] . ' ' . $usuario_data['usuario_apellido']); ?></h3>
                    <p><?php echo htmlspecialchars($usuario_data['usuario_email']); ?></p>
                </div>

                <?php if (!empty($mensaje)): ?>
                    <div class="message <?php echo $tipo_mensaje; ?>">
                        <?php 
                        $icon = $tipo_mensaje === 'success' ? '✓' : 
                               ($tipo_mensaje === 'error' ? '⚠️' : 'ℹ️');
                        echo $icon;
                        ?>
                        <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>

                <form class="reset-form" method="POST" action="" id="resetForm">
                    <div class="form-group">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <div class="password-container">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input"
                                placeholder="••••••••••••"
                                required
                                autocomplete="new-password"
                                minlength="6"
                                maxlength="200"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                👁️
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-feedback" id="passwordFeedback">
                            La contraseña debe tener al menos 6 caracteres con letras y números
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                        <div class="password-container">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input"
                                placeholder="••••••••••••"
                                required
                                autocomplete="new-password"
                                minlength="6"
                                maxlength="200"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                👁️
                            </button>
                        </div>
                        <div class="password-feedback" id="confirmFeedback"></div>
                    </div>

                    <button type="submit" class="reset-button" id="submitBtn">
                        🔐 Actualizar Contraseña
                    </button>
                </form>

                <div class="reset-links">
                    <a href="login.php" class="link">← Volver al Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
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

        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            const strengthBar = document.getElementById('strengthBar');
            const passwordFeedback = document.getElementById('passwordFeedback');
            const confirmFeedback = document.getElementById('confirmFeedback');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('resetForm');
            
            if (passwordInput) {
                passwordInput.focus();
                
                // Validación de fortaleza de contraseña
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    let feedback = [];
                    
                    // Criterios de fortaleza
                    if (password.length >= 6) strength++;
                    if (password.length >= 8) strength++;
                    if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
                    if (/[0-9]/.test(password)) strength++;
                    if (/[^A-Za-z0-9]/.test(password)) strength++;
                    
                    // Actualizar barra visual
                    strengthBar.className = 'password-strength-bar';
                    if (strength <= 2) {
                        strengthBar.classList.add('weak');
                        feedback.push('Contraseña débil');
                    } else if (strength <= 3) {
                        strengthBar.classList.add('medium');
                        feedback.push('Contraseña media');
                    } else {
                        strengthBar.classList.add('strong');
                        feedback.push('Contraseña fuerte');
                    }
                    
                    // Validaciones específicas
                    if (password.length < 6) {
                        feedback.push('Mínimo 6 caracteres');
                    }
                    if (!/[A-Za-z]/.test(password)) {
                        feedback.push('Debe contener letras');
                    }
                    if (!/[0-9]/.test(password)) {
                        feedback.push('Debe contener números');
                    }
                    
                    passwordFeedback.textContent = feedback.join(' • ');
                    
                    // Revalidar confirmación si tiene valor
                    if (confirmInput && confirmInput.value) {
                        validateConfirmation();
                    }
                    
                    updateSubmitButton();
                });
            }
            
            // Validación de confirmación
            function validateConfirmation() {
                if (!confirmInput || !passwordInput) return;
                
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                
                if (confirm === '') {
                    confirmFeedback.textContent = 'Repite la contraseña';
                    confirmFeedback.style.color = '#6b7280';
                } else if (password === confirm) {
                    confirmFeedback.textContent = '✓ Las contraseñas coinciden';
                    confirmFeedback.style.color = '#10b981';
                } else {
                    confirmFeedback.textContent = '✗ Las contraseñas no coinciden';
                    confirmFeedback.style.color = '#ef4444';
                }
                
                updateSubmitButton();
            }
            
            if (confirmInput) {
                confirmInput.addEventListener('input', validateConfirmation);
            }
            
            // Habilitar/deshabilitar botón de envío
            function updateSubmitButton() {
                if (!passwordInput || !confirmInput || !submitBtn) return;
                
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                const isValid = password.length >= 6 && 
                               /[A-Za-z]/.test(password) && 
                               /[0-9]/.test(password) && 
                               password === confirm;
                
                submitBtn.disabled = !isValid;
            }
            
            // Prevenir envío múltiple
            if (form) {
                form.addEventListener('submit', function() {
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '⏳ Actualizando...';
                    }
                });
            }
            
            // Auto-ocultar mensajes de éxito
            const successMessages = document.querySelectorAll('.message.success');
            successMessages.forEach(function(message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.3s ease';
                    setTimeout(function() {
                        if (message.parentElement) {
                            message.style.display = 'none';
                        }
                    }, 300);
                }, 8000);
            });
        });
    </script>
</body>
</html>