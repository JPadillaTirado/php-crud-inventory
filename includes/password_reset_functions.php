<?php
/**
 * FUNCIONES AUXILIARES PARA RECUPERACIÓN DE CONTRASEÑA
 * 
 * Funciones utilitarias para el manejo de tokens, validaciones y seguridad
 * del sistema de recuperación de contraseña
 */

require_once __DIR__ . '/../config/email_config.php';

/**
 * Genera un token seguro para recuperación de contraseña
 * 
 * @return string Token hexadecimal de 64 caracteres
 */
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Valida si un token es válido y no ha expirado
 * 
 * @param string $token Token a validar
 * @return array|false Array con datos del token o false si es inválido
 */
function isTokenValid($token) {
    global $conexion;
    
    if (empty($token) || strlen($token) !== 64) {
        return false;
    }
    
    $sql = "SELECT t.id, t.usuario_id, t.token, t.expira_en, t.usado, 
                   u.usuario_email, u.usuario_nombre, u.usuario_apellido
            FROM password_reset_tokens t
            JOIN usuario u ON t.usuario_id = u.usuario_id
            WHERE t.token = ? 
            AND t.expira_en > NOW() 
            AND t.usado = 0";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Marca un token como usado
 * 
 * @param string $token Token a marcar como usado
 * @return bool True si se marcó correctamente
 */
function markTokenAsUsed($token) {
    global $conexion;
    
    $sql = "UPDATE password_reset_tokens 
            SET usado = 1, used_at = NOW() 
            WHERE token = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $token);
    return $stmt->execute();
}

/**
 * Crear un nuevo token de recuperación
 * 
 * @param int $usuarioId ID del usuario
 * @param string $ipAddress IP del usuario (opcional)
 * @param string $userAgent User agent del navegador (opcional)
 * @return string|false Token generado o false en caso de error
 */
function createResetToken($usuarioId, $ipAddress = null, $userAgent = null) {
    global $conexion;
    
    // Invalidar tokens anteriores del mismo usuario
    invalidateUserTokens($usuarioId);
    
    $token = generateResetToken();
    $expiraEn = date('Y-m-d H:i:s', strtotime('+' . RESET_TOKEN_EXPIRY . ' minutes'));
    
    $sql = "INSERT INTO password_reset_tokens 
            (usuario_id, token, expira_en, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("issss", $usuarioId, $token, $expiraEn, $ipAddress, $userAgent);
    
    if ($stmt->execute()) {
        return $token;
    }
    
    return false;
}

/**
 * Invalida todos los tokens activos de un usuario
 * 
 * @param int $usuarioId ID del usuario
 * @return bool True si se invalidaron correctamente
 */
function invalidateUserTokens($usuarioId) {
    global $conexion;
    
    $sql = "UPDATE password_reset_tokens 
            SET usado = 1, used_at = NOW() 
            WHERE usuario_id = ? AND usado = 0";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    return $stmt->execute();
}

/**
 * Limpia tokens expirados y usados
 * 
 * @return int Número de tokens eliminados
 */
function cleanExpiredTokens() {
    global $conexion;
    
    $sql = "DELETE FROM password_reset_tokens 
            WHERE (expira_en < NOW() OR usado = 1) 
            AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    
    $conexion->query($sql);
    return $conexion->affected_rows;
}

/**
 * Registra un intento de recuperación de contraseña
 * 
 * @param string $email Email usado en el intento
 * @param bool $success Si el intento fue exitoso
 * @param string $ipAddress IP del usuario
 * @param string $userAgent User agent del navegador
 * @return bool True si se registró correctamente
 */
function logResetAttempt($email, $success, $ipAddress = null, $userAgent = null) {
    global $conexion;
    
    $sql = "INSERT INTO password_reset_attempts 
            (email, success, ip_address, user_agent) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("siss", $email, $success, $ipAddress, $userAgent);
    return $stmt->execute();
}

/**
 * Verifica si un email/IP ha excedido el límite de intentos
 * 
 * @param string $email Email a verificar
 * @param string $ipAddress IP a verificar
 * @return bool True si ha excedido el límite
 */
function hasExceededResetAttempts($email, $ipAddress) {
    global $conexion;
    
    $horaAtras = date('Y-m-d H:i:s', time() - RESET_COOLDOWN);
    
    // Verificar intentos por email
    $sql = "SELECT COUNT(*) as intentos 
            FROM password_reset_attempts 
            WHERE email = ? 
            AND attempt_time > ? 
            AND success = 0";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $email, $horaAtras);
    $stmt->execute();
    $result = $stmt->get_result();
    $emailAttempts = $result->fetch_assoc()['intentos'];
    
    // Verificar intentos por IP
    $sql = "SELECT COUNT(*) as intentos 
            FROM password_reset_attempts 
            WHERE ip_address = ? 
            AND attempt_time > ? 
            AND success = 0";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $ipAddress, $horaAtras);
    $stmt->execute();
    $result = $stmt->get_result();
    $ipAttempts = $result->fetch_assoc()['intentos'];
    
    return ($emailAttempts >= MAX_RESET_ATTEMPTS) || ($ipAttempts >= MAX_RESET_ATTEMPTS * 2);
}

/**
 * Obtiene información de un usuario por email
 * 
 * @param string $email Email del usuario
 * @return array|false Datos del usuario o false si no existe
 */
function getUserByEmail($email) {
    global $conexion;
    
    $sql = "SELECT usuario_id, usuario_nombre, usuario_apellido, usuario_email 
            FROM usuario 
            WHERE usuario_email = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Actualiza la contraseña de un usuario
 * 
 * @param int $usuarioId ID del usuario
 * @param string $nuevaPassword Nueva contraseña en texto plano
 * @return bool True si se actualizó correctamente
 */
function updateUserPassword($usuarioId, $nuevaPassword) {
    global $conexion;
    
    $hashedPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);
    
    $sql = "UPDATE usuario 
            SET usuario_clave = ? 
            WHERE usuario_id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $hashedPassword, $usuarioId);
    return $stmt->execute();
}

/**
 * Valida la fortaleza de una contraseña
 * 
 * @param string $password Contraseña a validar
 * @return array Array con 'valid' (bool) y 'errors' (array)
 */
function validatePasswordStrength($password) {
    $errors = [];
    $valid = true;
    
    if (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        $valid = false;
    }
    
    if (strlen($password) > 200) {
        $errors[] = 'La contraseña es demasiado larga';
        $valid = false;
    }
    
    if (!preg_match('/[A-Za-z]/', $password)) {
        $errors[] = 'La contraseña debe contener al menos una letra';
        $valid = false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'La contraseña debe contener al menos un número';
        $valid = false;
    }
    
    // Opcional: verificar caracteres especiales para mayor seguridad
    // if (!preg_match('/[^A-Za-z0-9]/', $password)) {
    //     $errors[] = 'La contraseña debe contener al menos un carácter especial';
    //     $valid = false;
    // }
    
    return [
        'valid' => $valid,
        'errors' => $errors
    ];
}

/**
 * Obtiene la IP real del usuario
 * 
 * @return string IP del usuario
 */
function getUserIP() {
    // Verificar si viene de un proxy
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * Obtiene el User Agent del navegador
 * 
 * @return string User Agent
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

/**
 * Sanitiza email de entrada
 * 
 * @param string $email Email a sanitizar
 * @return string Email sanitizado
 */
function sanitizeEmail($email) {
    return filter_var(trim(strtolower($email)), FILTER_SANITIZE_EMAIL);
}

/**
 * Genera estadísticas de intentos de recuperación (para admin)
 * 
 * @return array Estadísticas
 */
function getResetStatistics() {
    global $conexion;
    
    $stats = [];
    
    // Intentos en las últimas 24 horas
    $sql = "SELECT COUNT(*) as total 
            FROM password_reset_attempts 
            WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $result = $conexion->query($sql);
    $stats['attempts_24h'] = $result->fetch_assoc()['total'];
    
    // Tokens activos
    $sql = "SELECT COUNT(*) as total 
            FROM password_reset_tokens 
            WHERE expira_en > NOW() AND usado = 0";
    $result = $conexion->query($sql);
    $stats['active_tokens'] = $result->fetch_assoc()['total'];
    
    // Intentos exitosos hoy
    $sql = "SELECT COUNT(*) as total 
            FROM password_reset_attempts 
            WHERE success = 1 
            AND DATE(attempt_time) = CURDATE()";
    $result = $conexion->query($sql);
    $stats['successful_today'] = $result->fetch_assoc()['total'];
    
    return $stats;
}

/**
 * Limpia intentos antiguos de recuperación
 * 
 * @return int Número de registros eliminados
 */
function cleanOldAttempts() {
    global $conexion;
    
    $sql = "DELETE FROM password_reset_attempts 
            WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 7 DAY)";
    
    $conexion->query($sql);
    return $conexion->affected_rows;
}
?>