<?php
/**
 * CLASE PARA ENVÍO DE EMAILS - VERSIÓN CORREGIDA
 * 
 * Soluciona el problema del enlace hardcodeado y mejora la detección de URLs
 */

class EmailSender {
    private static $lastError = '';

    /**
     * Valida formato de email
     */
    public static function isValidEmail($email) {
        if (empty($email) || strlen($email) > (defined('MAX_EMAIL_LENGTH') ? MAX_EMAIL_LENGTH : 254)) {
            return false;
        }
        
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Envía correo de recuperación de contraseña
     * CORREGIDO: Ahora usa la URL correcta
     */
    public static function sendPasswordResetEmail($email, $token, $userName) {
        if (!self::isValidEmail($email)) {
            self::$lastError = 'Email inválido';
            return false;
        }

        $appName = defined('APP_NAME') ? APP_NAME : 'Sistema de Inventario';
        $subject = "Recuperación de Contraseña - " . $appName;
        
        // CORRECCIÓN PRINCIPAL: Usar la función correcta para generar el enlace
        $resetLink = self::generateCorrectResetLink($token);
        $message = self::getPasswordResetTemplate($userName, $resetLink, $token);

        return self::sendEmailImproved($email, $subject, $message);
    }

    /**
     * Genera el enlace de recuperación CORRECTO
     * Esta función soluciona el problema del path hardcodeado
     */
    private static function generateCorrectResetLink($token) {
        // Detectar la URL base del proyecto automáticamente
        $baseUrl = self::detectProjectBaseUrl();
        return $baseUrl . '/reset_password.php?token=' . urlencode($token);
    }
    
    /**
     * Detecta automáticamente la URL base del proyecto
     * Función mejorada que funciona en diferentes entornos
     */
    private static function detectProjectBaseUrl() {
        // Método 1: Si BASE_URL está definida y es válida, usarla
        if (defined('BASE_URL') && !empty(BASE_URL) && BASE_URL !== 'http://localhost/') {
            return rtrim(BASE_URL, '/');
        }
        
        // Método 2: Si SITE_URL está definida en email_config, usarla con detección de subdirectorio
        if (defined('SITE_URL') && !empty(SITE_URL)) {
            $siteUrl = rtrim(SITE_URL, '/');
            
            // Si estamos en un subdirectorio, detectarlo
            if (isset($_SERVER['SCRIPT_NAME'])) {
                $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
                $scriptPath = rtrim($scriptPath, '/\\');
                
                // Si el script está en un subdirectorio, agregarlo
                if ($scriptPath && $scriptPath !== '/' && $scriptPath !== '.') {
                    $siteUrl .= $scriptPath;
                }
            }
            
            return $siteUrl;
        }
        
        // Método 3: Detección automática completa
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Obtener el path del proyecto
        $scriptPath = '';
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
            $scriptPath = rtrim($scriptPath, '/\\');
            
            // Limpiar el path
            if ($scriptPath === '' || $scriptPath === '.' || $scriptPath === '/') {
                $scriptPath = '';
            }
        }
        
        return $protocol . $host . $scriptPath;
    }

    /**
     * Envío mejorado de emails usando mail() nativo de PHP
     */
    public static function sendEmailImproved($to, $subject, $message) {
        try {
            // Limpiar el destinatario
            $to = filter_var($to, FILTER_SANITIZE_EMAIL);
            if (!self::isValidEmail($to)) {
                throw new Exception('Email de destinatario inválido');
            }

            // Configurar headers
            $headers = self::buildHeaders();
            $parameters = self::buildMailParameters();

            // Enviar email
            $sent = mail($to, $subject, $message, $headers, $parameters);

            if (!$sent) {
                $error = error_get_last();
                throw new Exception('Error al enviar email: ' . ($error['message'] ?? 'Error desconocido'));
            }

            return true;

        } catch (Exception $e) {
            self::$lastError = $e->getMessage();
            
            if (defined('EMAIL_LOG_ERRORS') && EMAIL_LOG_ERRORS) {
                error_log('Error en EmailSender: ' . $e->getMessage());
            }
            
            return false;
        }
    }

    /**
     * Construye headers del email
     */
    private static function buildHeaders() {
        $fromEmail = defined('FROM_EMAIL') ? FROM_EMAIL : 'noreply@localhost';
        $fromName = defined('FROM_NAME') ? FROM_NAME : 'Sistema';
        
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'Reply-To: ' . $fromEmail;
        $headers[] = 'Return-Path: ' . $fromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headers[] = 'X-Priority: 3';
        $headers[] = 'Date: ' . date('r');

        return implode("\r\n", $headers);
    }

    /**
     * Construye parámetros adicionales para mail()
     */
    private static function buildMailParameters() {
        $params = [];
        
        $fromEmail = defined('FROM_EMAIL') ? FROM_EMAIL : '';
        if (!empty($fromEmail)) {
            $params[] = '-f' . $fromEmail;
        }
        
        return implode(' ', $params);
    }

    /**
     * Obtiene el último error
     */
    public static function getLastError() {
        return self::$lastError;
    }

    /**
     * Template HTML para correo de recuperación de contraseña
     * MEJORADO: Ahora incluye mejor información de debug
     */
    private static function getPasswordResetTemplate($userName, $resetLink, $token) {
        $appName = defined('APP_NAME') ? APP_NAME : 'Sistema de Inventario';
        $currentYear = date('Y');
        $safeUserName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
        $tokenExpiry = defined('RESET_TOKEN_EXPIRY') ? RESET_TOKEN_EXPIRY : 10;
        
        // Debug info para desarrollo
        $debugInfo = '';
        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
            $debugInfo = '
            <div style="background-color: #f8f9fa; border: 2px solid #6c757d; border-radius: 4px; padding: 15px; margin: 20px 0; font-family: monospace; font-size: 12px;">
                <strong>🐛 DEBUG INFO (Solo en desarrollo):</strong><br>
                <strong>Token:</strong> ' . $token . '<br>
                <strong>Enlace generado:</strong> ' . $resetLink . '<br>
                <strong>Fecha generación:</strong> ' . date('Y-m-d H:i:s') . '<br>
                <strong>Expira en:</strong> ' . $tokenExpiry . ' minutos<br>
                <strong>BASE_URL:</strong> ' . (defined('BASE_URL') ? BASE_URL : 'No definida') . '<br>
                <strong>SITE_URL:</strong> ' . (defined('SITE_URL') ? SITE_URL : 'No definida') . '<br>
            </div>';
        }
        
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="background-color: #2980b9; color: #fff; padding: 30px; text-align: center;">
            <h1 style="margin: 0; font-size: 28px; font-weight: bold;">🔐 Recuperación de Contraseña</h1>
        </div>
        
        <div style="padding: 40px 30px;">
            <div style="font-size: 18px; margin-bottom: 20px; color: #333;">¡Hola ' . $safeUserName . '!</div>
            
            <div style="font-size: 16px; line-height: 1.6; color: #555; margin-bottom: 30px;">
                Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en <strong>' . $appName . '</strong>.
            </div>
            
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 15px; margin: 20px 0;">
                <strong>⏰ Importante:</strong> Este enlace expirará en <strong>' . $tokenExpiry . ' minutos</strong> por motivos de seguridad.
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $resetLink . '" style="display: inline-block; background-color: #3498db; color: #fff; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">🔑 Restablecer mi Contraseña</a>
            </div>
            
            <div style="background-color: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; padding: 15px; margin: 20px 0;">
                <strong>🛡️ Seguridad:</strong> Si no solicitaste este cambio, puedes ignorar este correo de forma segura. Tu contraseña actual permanecerá sin cambios.
            </div>
            
            <hr style="border: none; border-top: 1px solid #e9ecef; margin: 30px 0;">
            
            <p><strong>¿Problemas con el botón?</strong></p>
            <p>Copia y pega este enlace en tu navegador:</p>
            <div style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 14px; border: 1px solid #dee2e6;">
                ' . $resetLink . '
            </div>
            
            ' . $debugInfo . '
        </div>
        
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px;">
            <p><strong>' . $appName . '</strong></p>
            <p>&copy; ' . $currentYear . ' Todos los derechos reservados.</p>
            <p style="margin-top: 15px; font-size: 12px;">
                Este es un correo automático, por favor no respondas a esta dirección.
            </p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Envía correo de confirmación de cambio de contraseña
     */
    public static function sendPasswordChangeConfirmation($email, $userName) {
        $appName = defined('APP_NAME') ? APP_NAME : 'Sistema de Inventario';
        $subject = "Contraseña Cambiada - " . $appName;
        $message = self::getPasswordChangeTemplate($userName);
        
        return self::sendEmailImproved($email, $subject, $message);
    }
    
    /**
     * Template para confirmación de cambio de contraseña
     */
    private static function getPasswordChangeTemplate($userName) {
        $appName = defined('APP_NAME') ? APP_NAME : 'Sistema de Inventario';
        $currentYear = date('Y');
        $safeUserName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
        
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseña Actualizada</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="background-color: #27ae60; color: #fff; padding: 30px; text-align: center;">
            <h1 style="margin: 0; font-size: 28px; font-weight: bold;">✅ Contraseña Actualizada</h1>
        </div>
        
        <div style="padding: 40px 30px; text-align: center;">
            <div style="font-size: 18px; margin-bottom: 20px; color: #333;">¡Hola ' . $safeUserName . '!</div>
            
            <div style="font-size: 16px; line-height: 1.6; color: #555; margin-bottom: 30px;">
                Tu contraseña en <strong>' . $appName . '</strong> ha sido actualizada exitosamente el <strong>' . date('d/m/Y H:i:s') . '</strong>.
            </div>
            
            <div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 15px; margin: 20px 0;">
                <strong>🔒 Seguridad:</strong> Si no realizaste este cambio, contacta a nuestro equipo de soporte inmediatamente.
            </div>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px;">
            <p><strong>' . $appName . '</strong></p>
            <p>&copy; ' . $currentYear . ' Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>';
    }
}
?>