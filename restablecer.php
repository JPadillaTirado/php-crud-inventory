<?php

// Incluir archivos de configuración
require_once 'config/config.php';
require_once 'config/conexion.php';

$error = '';
$token_valido = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Verificar si el token existe y no ha expirado
    $stmt = $conexion->prepare("SELECT id FROM admin WHERE reset_token = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $token_valido = true;
    } else {
        $error = "El token no es válido o ha expirado. Por favor, solicita un nuevo enlace.";
    }
    $stmt->close();
} else {
    $error = "No se proporcionó un token.";
}

// Lógica para actualizar la contraseña si el formulario se envía
if ($token_valido && $_SERVER["REQUEST_METHOD"] == "POST") {
    $password_nueva = $_POST['password'];
    $password_confirmar = $_POST['password_confirmar'];

    if ($password_nueva === $password_confirmar) {
        // Hashear la nueva contraseña
        $password_hash = password_hash($password_nueva, PASSWORD_BCRYPT);

        // Actualizar la contraseña y limpiar el token
        $stmt_update = $conexion->prepare("UPDATE admin SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE reset_token = ?");
        $stmt_update->bind_param("ss", $password_hash, $token);
        $stmt_update->execute();
        
        echo "<h2>¡Contraseña actualizada!</h2><p>Tu contraseña ha sido restablecida correctamente. Ya puedes iniciar sesión.</p>";
        $stmt_update->close();
        $conexion->close();
        exit(); // Termina el script para no mostrar el formulario de nuevo
    } else {
        $error = "Las contraseñas no coinciden.";
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Contraseña</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Crear Nueva Contraseña</h2>
        <?php if ($token_valido): ?>
            <form method="POST" action="">
                <?php if ($error) { echo "<p class='error'>$error</p>"; } ?>
                <label for="password">Nueva Contraseña:</label>
                <input type="password" name="password" required>
                <br><br>
                <label for="password_confirmar">Confirmar Contraseña:</label>
                <input type="password" name="password_confirmar" required>
                <br><br>
                <input type="submit" value="Guardar Contraseña">
            </form>
        <?php else: ?>
            <p class="error"><?php echo $error; ?></p>
            <a href="solicitar_restablecimiento.html">Solicitar nuevo enlace</a>
        <?php endif; ?>
    </div>
</body>
</html>
<?php if($token_valido) $conexion->close(); ?>

