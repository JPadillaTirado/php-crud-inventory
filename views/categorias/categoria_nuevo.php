<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';

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
    <title>Nueva Categoría - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Nueva Categoría</h1>
            <nav>
                <ul>
                    <li><a href="../../index.php">Inicio</a></li>
                    <li><a href="../productos/producto_listar.php">Productos</a></li>
                    <li><a href="categoria_listar.php" class="active">Categorías</a></li>
                    <li><a href="../usuarios/usuario_listar.php">Usuarios</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="formulario-seccion">
            <h2>Agregar Nueva Categoría</h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="mensaje error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- El formulario ahora apunta al controlador en la nueva ubicación -->
            <form action="../../controllers/categoria_guardar.php" method="POST">
                <div class="grupo-formulario">
                    <label for="nombre">Nombre de la Categoría:</label>
                    <input type="text" id="nombre" name="nombre" maxlength="50" required>
                </div>
                
                <div class="grupo-formulario">
                    <label for="ubicacion">Ubicación:</label>
                    <input type="text" id="ubicacion" name="ubicacion" maxlength="150" required>
                    <small>Ejemplo: Pasillo 3, Estante B</small>
                </div>
                
                <div class="acciones-formulario">
                    <a href="categoria_listar.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Categoría</button>
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