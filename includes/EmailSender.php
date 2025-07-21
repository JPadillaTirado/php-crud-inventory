<?php
/**
 * CLASE PARA ENVÍO DE CORREOS ELECTRÓNICOS
 * 
 * Maneja el envío de correos para recuperación de contraseña
 * Compatible con la configuración SMTP de jorgepadilla.co
 */

require_once __DIR__ . '/../config/email_config.php';

class EmailSender {
    
    private static $lastError = '';
    
    /**
     * Envía correo de recuperación de contraseña
     */
    public static function sendPasswordResetEmail($email, $token, $userName) {
        try {
            // Validar configuración antes de enviar
            $configErrors = validateEmailConfig();
            if (!empty($configErrors)) {
                self::$lastError = 'Error de configuración: ' . implode(', ', $configErrors);
                return false;
            }
            
            $resetLink = self::generateResetLink($token);
            $subject = "Recuperación de Contraseña - " . (defined('APP_NAME') ? APP_NAME : 'Sistema de Inventario');
            $message = self::getPasswordResetTemplate($userName, $resetLink, $token);
            $headers = self::getEmailHeaders();
            
            // Log del intento (si está habilitado)
            if (EMAIL_DEBUG) {
                error_log("Enviando correo de recuperación a: $email");
            }
            
            // Intentar envío
            $sent = mail($email, $subject, $message, $headers);
            
            if (!$sent) {
                self::$lastError = 'Error al enviar correo';
                if (EMAIL_LOG_ERRORS) {
                    error_log("Error enviando correo a $email: " . error_get_last()['message']);
                }
            }
            
            return $sent;
            
        } catch (Exception $e) {
            self::$lastError = 'Excepción al enviar correo: ' . $e->getMessage();
            if (EMAIL_LOG_ERRORS) {
                error_log("Excepción en EmailSender: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Genera el enlace de recuperación
     */
    private static function generateResetLink($token) {
        return SITE_URL . "/reset_password.php?token=" . urlencode($token);
    }
    
    /**
     * Template HTML para correo de recuperación de contraseña
     */
    private static function getPasswordResetTemplate($userName, $resetLink, $token) {
        $appName = defined('APP_NAME') ? APP_NAME : 'Sistema de Inventario';
        $currentYear = date('Y');
        $expiryMinutes = RESET_TOKEN_EXPIRY;
        
        return "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Recuperación de Contraseña</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            margin: 0; 
            padding: 0; 
            background-color: #f5f5f5;
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header { 
            background: linear-gradient(135deg, #2980b9, #3498db); 
            color: white; 
            padding: 30px 20px; 
            text-align: center; 
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content { 
            padding: 40px 30px; 
            background: white; 
        }
        .content h2 {
            color: #2c3e50;
            margin-top: 0;
            font-size: 20px;
        }
        .button { 
            display: inline-block; 
            padding: 15px 30px; 
            background: linear-gradient(135deg, #2980b9, #3498db); 
            color: white; 
            text-decoration: none; 
            border-radius: 8px; 
            margin: 25px 0;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .info-box {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #0d47a1;
        }
        .footer { 
            padding: 30px 20px; 
            text-align: center; 
            font-size: 14px; 
            color: #666; 
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        .token-info {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
        }
        @media (max-width: 600px) {
            .content {
                padding: 20px 15px;
            }
            .header {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔐 Recuperación de Contraseña</h1>
            <p style='margin: 10px 0 0 0; opacity: 0.9;'>{$appName}</p>
        </div>
        
        <div class='content'>
            <h2>Hola, {$userName}</h2>
            
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en {$appName}.</p>
            
            <p>Si fuiste tú quien solicitó este cambio, haz clic en el siguiente botón para crear una nueva contraseña:</p>
            
            <div class='button-container'>
                <a href='{$resetLink}' class='button'>🔑 Restablecer Mi Contraseña</a>
            </div>
            
            <div class='warning-box'>
                <strong>⏰ Importante:</strong> Este enlace expirará en <strong>{$expiryMinutes} minutos</strong> por seguridad.
            </div>
            
            <div class='info-box'>
                <strong>🛡️ Seguridad:</strong> Si no solicitaste este cambio, puedes ignorar este correo de forma segura. Tu contraseña actual permanecerá sin cambios.
            </div>
            
            <hr style='border: none; border-top: 1px solid #e9ecef; margin: 30px 0;'>
            
            <p><strong>¿Problemas con el botón?</strong></p>
            <p>Copia y pega este enlace en tu navegador:</p>
            <p style='word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 14px;'>{$resetLink}</p>
            
            " . (EMAIL_DEBUG ? "<div class='token-info'>Token de debug: {$token}</div>" : "") . "
        </div>
        
        <div class='footer'>
            <p><strong>{$appName}</strong></p>
            <p>&copy; {$currentYear} Todos los derechos reservados.</p>
            <p style='margin-top: 15px; font-size: 12px;'>
                Este es un correo automático, por favor no respondas a esta dirección.
            </p>
        </div>
    </div>
</body>
</html>";
    }
    
    /**
     * Genera headers para el correo
     */
    private static function getEmailHeaders() {
        $headers = [];
        
        // Headers básicos
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        
        // Remitente
        $headers[] = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">";
        $headers[] = "Reply-To: " . FROM_EMAIL;
        $headers[] = "Return-Path: " . FROM_EMAIL;
        
        // Headers de seguridad y anti-spam
        $headers[] = "X-Mailer: PHP/" . phpversion() . " (Sistema de Inventario)";
        $headers[] = "X-Priority: 3";
        $headers[] = "X-MSMail-Priority: Normal";
        $headers[] = "Importance: Normal";
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Envía correo de confirmación de cambio de contraseña
     */
    public static function sendPasswordChangeConfirmation($email, $userName) {
        $subject = "Contraseña Cambiada - " . (defined('APP_NAME') ? APP_NAME : 'Sistema de Inventario');
        $message = self::getPasswordChangeTemplate($userName);
        $headers = self::getEmailHeaders();
        
        return mail($email, $subject, $message, $headers);
    }
    
    /**
     * Template para confirmación de cambio de contraseña
     */
    private static function getPasswordChangeTemplate($userName) {
        $appName = defined('APP_NAME') ? APP_NAME : 'Sistema de Inventario';
        $currentYear = date('Y');
        $currentDate = date('d/m/Y H:i');
        
        return "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: white; }
        .header { background: #27ae60; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .success-icon { font-size: 48px; text-align: center; margin-bottom: 20px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; background: #f8f9fa; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>✅ Contraseña Actualizada</h1>
        </div>
        <div class='content'>
            <div class='success-icon'>🎉</div>
            <h2>¡Hola, {$userName}!</h2>
            <p>Te confirmamos que tu contraseña ha sido cambiada exitosamente el {$currentDate}.</p>
            <p>Si no realizaste este cambio, contacta inmediatamente al administrador del sistema.</p>
            <p>Gracias por usar {$appName}.</p>
        </div>
        <div class='footer'>
            <p>&copy; {$currentYear} {$appName}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>";
    }
    
    /**
     * Obtiene el último error ocurrido
     */
    public static function getLastError() {
        return self::$lastError;
    }
    
    /**
     * Limpia el último error
     */
    public static function clearLastError() {
        self::$lastError = '';
    }
    
    /**
     * Valida formato de email
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= MAX_EMAIL_LENGTH;
    }
}
?>