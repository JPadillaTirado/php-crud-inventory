<?php
/**
 * PÁGINA DE SOLICITUD DE RECUPERACIÓN DE CONTRASEÑA
 * 
 * Permite a los usuarios solicitar un enlace de recuperación
 * enviado a su correo electrónico registrado
 */

session_start();

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/conexion.php';
require_once 'config/email_config.php';
require_once 'includes/password_reset_functions.php';
require_once 'includes/EmailSender.php';

$mensaje = '';
$tipo_mensaje = '';
$email_enviado = false;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeEmail($_POST['email'] ?? '');
    $userIP = getUserIP();
    $userAgent = getUserAgent();
    
    // Validaciones básicas
    if (empty($email)) {
        $mensaje = 'Por favor, ingresa tu correo electrónico.';
        $tipo_mensaje = 'error';
    } elseif (!EmailSender::isValidEmail($email)) {
        $mensaje = 'Por favor, ingresa un correo electrónico válido.';
        $tipo_mensaje = 'error';
    } elseif (hasExceededResetAttempts($email, $userIP)) {
        $mensaje = 'Has excedido el número máximo de intentos. Intenta nuevamente más tarde.';
        $tipo_mensaje = 'error';
    } else {
        // Buscar usuario por email
        $usuario = getUserByEmail($email);
        
        // Registrar intento (siempre, para seguridad)
        logResetAttempt($email, $usuario !== false, $userIP, $userAgent);
        
        if ($usuario) {
            // Crear token de recuperación
            $token = createResetToken($usuario['usuario_id'], $userIP, $userAgent);
            
            if ($token) {
                // Enviar correo
                $nombreCompleto = $usuario['usuario_nombre'] . ' ' . $usuario['usuario_apellido'];
                $emailEnviado = EmailSender::sendPasswordResetEmail($email, $token, $nombreCompleto);
                
                if ($emailEnviado) {
                    $email_enviado = true;
                    $mensaje = '¡Correo enviado! Revisa tu bandeja de entrada y sigue las instrucciones.';
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Error al enviar el correo. Intenta nuevamente más tarde.';
                    $tipo_mensaje = 'error';
                    
                    // Log del error para debugging
                    if (EMAIL_LOG_ERRORS) {
                        error_log('Error enviando correo de recuperación: ' . EmailSender::getLastError());
                    }
                }
            } else {
                $mensaje = 'Error interno. Intenta nuevamente más tarde.';
                $tipo_mensaje = 'error';
            }
        } else {
            // Por seguridad, mostrar el mismo mensaje aunque el email no exista
            $email_enviado = true;
            $mensaje = '¡Correo enviado! Revisa tu bandeja de entrada y sigue las instrucciones.';
            $tipo_mensaje = 'success';
        }
    }
}

// Limpiar tokens expirados (mantenimiento)
if (rand(1, 100) <= 5) { // 5% de probabilidad
    cleanExpiredTokens();
    cleanOldAttempts();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?php echo APP_NAME; ?></title>
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
        
        /* Header con logo */
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
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* Container principal */
        .recovery-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .recovery-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 48px;
            width: 100%;
            max-width: 440px;
            border: 1px solid #e2e8f0;
        }
        
        .recovery-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .recovery-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
            color: white;
        }
        
        .recovery-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .recovery-subtitle {
            font-size: 16px;
            color: #64748b;
            font-weight: 400;
            line-height: 1.5;
        }
        
        /* Formulario */
        .recovery-form {
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
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input::placeholder {
            color: #9ca3af;
        }
        
        .recovery-button {
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
        
        .recovery-button:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .recovery-button:active {
            transform: translateY(0);
        }
        
        .recovery-button:disabled {
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
            align-items: center;
            gap: 8px;
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
        .recovery-links {
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
        
        /* Success state */
        .success-state {
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
            color: white;
        }
        
        .success-title {
            font-size: 24px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
        }
        
        .success-description {
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        .success-tips {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            text-align: left;
            margin: 20px 0;
        }
        
        .success-tips h4 {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .success-tips ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .success-tips li {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 4px;
            padding-left: 16px;
            position: relative;
        }
        
        .success-tips li:before {
            content: "•";
            color: #3b82f6;
            position: absolute;
            left: 0;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .header {
                padding: 16px 20px;
            }
            
            .recovery-card {
                padding: 32px 24px;
                border-radius: 16px;
                margin: 0 16px;
            }
            
            .recovery-title {
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
        
        .recovery-card {
            animation: slideUp 0.6s ease-out;
        }
        
        .header {
            animation: slideUp 0.6s ease-out 0.2s both;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <div class="logo-icon">🔐</div>
            <div class="logo-text">Recuperación</div>
        </div>
    </div>

    <!-- Container principal -->
    <div class="recovery-container">
        <div class="recovery-card">
            <?php if ($email_enviado): ?>
                <!-- Estado de éxito -->
                <div class="success-state">
                    <div class="success-icon">✉️</div>
                    <h1 class="success-title">¡Correo Enviado!</h1>
                    <p class="success-description">
                        Hemos enviado un enlace de recuperación a tu correo electrónico. 
                        Revisa tu bandeja de entrada y sigue las instrucciones.
                    </p>
                    
                    <div class="success-tips">
                        <h4>💡 Consejos importantes:</h4>
                        <ul>
                            <li>El enlace expirará en <?php echo RESET_TOKEN_EXPIRY; ?> minutos</li>
                            <li>Revisa también tu carpeta de spam o correo no deseado</li>
                            <li>El enlace solo puede usarse una vez</li>
                            <li>Si no recibes el correo, verifica que sea la dirección correcta</li>
                        </ul>
                    </div>
                    
                    <div class="recovery-links">
                        <a href="login.php" class="link">← Volver al Login</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Formulario de recuperación -->
                <div class="recovery-header">
                    <div class="recovery-icon">🔑</div>
                    <h1 class="recovery-title">¿Olvidaste tu contraseña?</h1>
                    <p class="recovery-subtitle">
                        No te preocupes. Ingresa tu correo electrónico y te enviaremos 
                        un enlace para crear una nueva contraseña.
                    </p>
                </div>

                <?php if (!empty($mensaje)): ?>
                    <div class="message <?php echo $tipo_mensaje; ?>">
                        <?php 
                        $icon = $tipo_mensaje === 'success' ? '✓' : 
                               ($tipo_mensaje === 'error' ? '⚠️' : 'ℹ️');
                        echo $icon;
                        ?>
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <form class="recovery-form" method="POST" action="">
                    <div class="form-group">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input"
                            placeholder="tu@correo.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required
                            autocomplete="email"
                            maxlength="<?php echo MAX_EMAIL_LENGTH; ?>"
                        >
                    </div>

                    <button type="submit" class="recovery-button">
                        🔄 Enviar Enlace de Recuperación
                    </button>
                </form>

                <div class="recovery-links">
                    <a href="login.php" class="link">← Volver al Login</a>
                    <span style="margin: 0 12px; color: #d1d5db;">|</span>
                    <a href="views/usuarios/usuario_nuevo.php" class="link">Crear Cuenta Nueva</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const form = document.querySelector('.recovery-form');
            const submitButton = document.querySelector('.recovery-button');
            
            // Auto-focus en el campo de email
            if (emailInput) {
                emailInput.focus();
            }
            
            // Validación en tiempo real
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    const email = this.value.trim();
                    const isValid = email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                    
                    if (submitButton) {
                        submitButton.disabled = !isValid;
                        submitButton.style.opacity = isValid ? '1' : '0.6';
                    }
                });
            }
            
            // Prevenir envíos múltiples
            if (form) {
                form.addEventListener('submit', function() {
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '⏳ Enviando...';
                        submitButton.classList.add('pulse');
                    }
                });
            }
            
            // Auto-ocultar mensajes después de 10 segundos
            const messages = document.querySelectorAll('.message');
            messages.forEach(function(message) {
                if (message.classList.contains('success') || message.classList.contains('info')) {
                    setTimeout(function() {
                        message.style.opacity = '0';
                        message.style.transition = 'opacity 0.3s ease';
                        setTimeout(function() {
                            if (message.parentElement) {
                                message.style.display = 'none';
                            }
                        }, 300);
                    }, 10000);
                }
            });
        });
    </script>
</body>
</html>