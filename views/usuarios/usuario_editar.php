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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Editar Usuario - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Editar Usuario</h1>
            <nav>
                <ul>
                    <li><a href="../../index.php">Inicio</a></li>
                    <li><a href="../productos/producto_listar.php">Productos</a></li>
                    <li><a href="../categorias/categoria_listar.php">Categorías</a></li>
                    <li><a href="usuario_listar.php" class="active">Usuarios</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="formulario-seccion">
            <h2>Editar Usuario #<?php echo $usuario_id; ?></h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="mensaje error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- El formulario apunta al controlador en la nueva ubicación -->
            <form action="../../controllers/usuario_actualizar.php" method="POST">
                <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">
                
                <div class="grupo-formulario">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" maxlength="40" required
                           value="<?php echo htmlspecialchars($usuario['usuario_nombre']); ?>">
                </div>
                
                <div class="grupo-formulario">
                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" maxlength="40" required
                           value="<?php echo htmlspecialchars($usuario['usuario_apellido']); ?>">
                </div>
                
                <div class="grupo-formulario">
                    <label for="usuario">Nombre de Usuario:</label>
                    <input type="text" id="usuario" name="usuario" maxlength="20" required
                           value="<?php echo htmlspecialchars($usuario['usuario_usuario']); ?>">
                    <small>El nombre de usuario debe ser único y tener entre 4 y 20 caracteres.</small>
                </div>
                
                <div class="grupo-formulario">
                    <label for="email">Correo Electrónico:</label>
                    <input type="email" id="email" name="email" maxlength="70" required
                           value="<?php echo htmlspecialchars($usuario['usuario_email']); ?>">
                    <small>Ejemplo: usuario@ejemplo.com</small>
                </div>
                
                <!-- Sección especial para cambio de contraseña - es opcional -->
                <div class="grupo-formulario">
                    <label for="clave">Nueva Contraseña (opcional):</label>
                    <input type="password" id="clave" name="clave" maxlength="200">
                    <small>Deje en blanco para mantener la contraseña actual. Si desea cambiarla, la nueva contraseña debe tener al menos 6 caracteres y contener letras y números.</small>
                </div>
                
                <div class="grupo-formulario">
                    <label for="confirmar_clave">Confirmar Nueva Contraseña:</label>
                    <input type="password" id="confirmar_clave" name="confirmar_clave" maxlength="200">
                    <small>Repita la nueva contraseña para confirmar.</small>
                </div>
                
                <div class="acciones-formulario">
                    <a href="usuario_listar.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                </div>
            </form>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> - <?php echo APP_NAME; ?></p>
        </div>
    </footer>
</body>
</html>