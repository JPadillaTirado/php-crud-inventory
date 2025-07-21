<?php
/**
 * PÁGINA DE RESTABLECIMIENTO DE CONTRASEÑA - VERSIÓN CORREGIDA
 * 
 * Procesa el token enviado por correo y permite al usuario
 * establecer una nueva contraseña
 */

// CORRECCIÓN: Configurar zona horaria al inicio
date_default_timezone_set('America/Bogota');

session_start();

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/conexion.php';
require_once 'config/email_config.php';
require_once 'includes/password_reset_functions.php';
require_once 'includes/EmailSender.php';

// CORRECCIÓN: Configurar zona horaria en MySQL también
$conexion->query("SET time_zone = '-05:00'");

$token = $_GET['token'] ?? '';
$mensaje = '';
$tipo_mensaje = '';
$token_valido = false;
$usuario_data = null;
$password_cambiada = false;

// Debug temporal para verificar el proceso
$debug_info = [];
if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
    $debug_info[] = "Token recibido: " . $token;
    $debug_info[] = "Longitud token: " . strlen($token);
    $debug_info[] = "Hora actual PHP: " . date('Y-m-d H:i:s');
    
    // Verificar hora MySQL
    $mysql_time_result = $conexion->query("SELECT NOW() as current_time");
    $mysql_time = $mysql_time_result->fetch_assoc()['current_time'];
    $debug_info[] = "Hora actual MySQL: " . $mysql_time;
}

// Validar token
if (!empty($token)) {
    if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
        $debug_info[] = "Iniciando validación de token...";
    }
    
    $usuario_data = isTokenValid($token);
    
    if ($usuario_data) {
        $token_valido = true;
        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
            $debug_info[] = "Token VÁLIDO - Usuario: " . $usuario_data['usuario_nombre'];
        }
    } else {
        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
            // Debug adicional para entender por qué falló
            $debug_sql = "SELECT t.*, 
                                 CASE WHEN t.expira_en > NOW() THEN 'NO EXPIRADO' ELSE 'EXPIRADO' END as estado_expiracion,
                                 TIMESTAMPDIFF(MINUTE, NOW(), t.expira_en) as minutos_restantes
                          FROM password_reset_tokens t 
                          WHERE t.token = ?";
            $debug_stmt = $conexion->prepare($debug_sql);
            $debug_stmt->bind_param("s", $token);
            $debug_stmt->execute();
            $debug_result = $debug_stmt->get_result();
            
            if ($debug_result->num_rows > 0) {
                $debug_data = $debug_result->fetch_assoc();
                $debug_info[] = "Token encontrado en BD pero INVÁLIDO:";
                $debug_info[] = "- Estado expiración: " . $debug_data['estado_expiracion'];
                $debug_info[] = "- Minutos restantes: " . $debug_data['minutos_restantes'];
                $debug_info[] = "- Usado: " . ($debug_data['usado'] ? 'SÍ' : 'NO');
                $debug_info[] = "- Expira en: " . $debug_data['expira_en'];
            } else {
                $debug_info[] = "Token NO encontrado en la base de datos";
            }
        }
        
        $mensaje = '⚠️ El enlace de recuperación no es válido o ha expirado. Solicita un nuevo enlace.';
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

// Limpiar tokens expirados (mantenimiento automático)
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
    <title>Restablecer Contraseña - <?php echo defined('APP_NAME') ? APP_NAME : 'Sistema'; ?></title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .reset-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 40px 30px;
        }
        
        .reset-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .reset-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .reset-subtitle {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.5;
        }
        
        .reset-content {
            padding: 40px 30px;
        }
        
        .message {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s;
            background: #f9fafb;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 12px 24px;
            font-size: 14px;
            display: inline-block;
            margin-top: 16px;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .success-content {
            text-align: center;
        }
        
        .success-icon {
            font-size: 64px;
            color: #10b981;
            margin-bottom: 24px;
        }
        
        .password-requirements {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .password-requirements h4 {
            color: #0369a1;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .password-requirements ul {
            color: #0369a1;
            font-size: 13px;
            margin-left: 16px;
        }
        
        .password-requirements li {
            margin-bottom: 4px;
        }
        
        .debug-info {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            font-family: monospace;
            font-size: 12px;
        }
        
        .debug-info h4 {
            color: #374151;
            margin-bottom: 8px;
            font-family: 'Inter', sans-serif;
        }
        
        .back-link {
            text-align: center;
            margin-top: 24px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <?php if ($password_cambiada): ?>
            <!-- Éxito -->
            <div class="reset-header">
                <div class="reset-icon">✅</div>
                <h1 class="reset-title">¡Contraseña Actualizada!</h1>
                <p class="reset-subtitle">Tu contraseña ha sido cambiada exitosamente</p>
            </div>
            
            <div class="reset-content">
                <div class="success-content">
                    <div class="success-icon">🎉</div>
                    <div class="message success">
                        ✓ <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                    <p style="color: #6b7280; margin-bottom: 24px;">
                        Tu contraseña ha sido actualizada de forma segura. Ya puedes iniciar sesión con tu nueva contraseña.
                    </p>
                    <a href="login.php" class="btn">Ir al Login</a>
                </div>
            </div>
            
        <?php elseif ($token_valido): ?>
            <!-- Formulario para nueva contraseña -->
            <div class="reset-header">
                <div class="reset-icon">🔑</div>
                <h1 class="reset-title">Nueva Contraseña</h1>
                <p class="reset-subtitle">Crea una contraseña segura para tu cuenta</p>
            </div>
            
            <div class="reset-content">
                <?php if (!empty($mensaje)): ?>
                    <div class="message <?php echo $tipo_mensaje; ?>">
                        <?php 
                        $icon = $tipo_mensaje === 'success' ? '✓' : '⚠️';
                        echo $icon . ' ' . $mensaje;
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="password-requirements">
                    <h4>🛡️ Requisitos de la contraseña:</h4>
                    <ul>
                        <li>Mínimo 6 caracteres</li>
                        <li>Al menos una letra</li>
                        <li>Al menos un número</li>
                        <li>Evita usar información personal</li>
                    </ul>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Ingresa tu nueva contraseña" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                               placeholder="Confirma tu nueva contraseña" required>
                    </div>
                    
                    <button type="submit" class="btn">🔒 Actualizar Contraseña</button>
                </form>
                
                <div class="back-link">
                    <a href="login.php">← Volver al Login</a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Error / Token inválido -->
            <div class="reset-header">
                <div class="reset-icon">⚠️</div>
                <h1 class="reset-title">Enlace Inválido</h1>
                <p class="reset-subtitle">El enlace de recuperación no es válido o ha expirado</p>
            </div>
            
            <div class="reset-content">
                <div class="message error">
                    ⚠️ <?php echo htmlspecialchars($mensaje); ?>
                </div>
                
                <p style="color: #6b7280; margin-bottom: 24px; text-align: center;">
                    Los enlaces de recuperación expiran en <?php echo defined('RESET_TOKEN_EXPIRY') ? RESET_TOKEN_EXPIRY : 10; ?> minutos por seguridad.
                    Por favor, solicita un nuevo enlace de recuperación.
                </p>
                
                <a href="forgot_password.php" class="btn">📧 Solicitar Nuevo Enlace</a>
                
                <div class="back-link">
                    <a href="login.php">← Volver al Login</a>
                </div>
                
                <?php if (defined('EMAIL_DEBUG') && EMAIL_DEBUG && !empty($debug_info)): ?>
                    <div class="debug-info">
                        <h4>🐛 Información de Debug:</h4>
                        <?php foreach ($debug_info as $info): ?>
                            <div><?php echo htmlspecialchars($info); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Validación en tiempo real de contraseñas
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password && confirmPassword) {
                function validatePasswords() {
                    if (confirmPassword.value && password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Las contraseñas no coinciden');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                password.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);
            }
        });
    </script>
</body>
</html>