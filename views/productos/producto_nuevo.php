<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}

// Consultar las categorías para el select
$sql_categorias = "SELECT categoria_id, categoria_nombre FROM categoria ORDER BY categoria_nombre ASC";
$resultado_categorias = $conexion->query($sql_categorias);

// Consultar usuarios para el select (asumiendo que se necesita asignar un usuario responsable)
$sql_usuarios = "SELECT usuario_id, usuario_nombre, usuario_apellido FROM usuario ORDER BY usuario_nombre ASC";
$resultado_usuarios = $conexion->query($sql_usuarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Nuevo Producto - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Nuevo Producto</h1>
            <nav>
                <ul>
                    <li><a href="../../index.php">Inicio</a></li>
                    <li><a href="producto_listar.php" class="active">Productos</a></li>
                    <li><a href="../categorias/categoria_listar.php">Categorías</a></li>
                    <li><a href="../usuarios/usuario_listar.php">Usuarios</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="formulario-seccion">
            <h2>Agregar Nuevo Producto</h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="mensaje error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Nota: El action apunta a producto_guarda.php (sin r) según tu estructura -->
            <form action="../../controllers/producto_guarda.php" method="POST" enctype="multipart/form-data">
                <div class="grupo-formulario">
                    <label for="codigo">Código del Producto:</label>
                    <input type="text" id="codigo" name="codigo" maxlength="70" required>
                </div>
                
                <div class="grupo-formulario">
                    <label for="nombre">Nombre del Producto:</label>
                    <input type="text" id="nombre" name="nombre" maxlength="70" required>
                </div>
                
                <div class="grupo-formulario">
                    <label for="precio">Precio ($):</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                </div>
                
                <div class="grupo-formulario">
                    <label for="stock">Stock (unidades):</label>
                    <input type="number" id="stock" name="stock" min="0" required>
                </div>
                
                <div class="grupo-formulario">
                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php 
                        if ($resultado_categorias && $resultado_categorias->num_rows > 0) {
                            while ($categoria = $resultado_categorias->fetch_assoc()) {
                                echo '<option value="' . $categoria['categoria_id'] . '">' . 
                                     htmlspecialchars($categoria['categoria_nombre']) . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>No hay categorías disponibles</option>';
                        }
                        ?>
                    </select>
                    <small><a href="../categorias/categoria_nuevo.php">Agregar nueva categoría</a></small>
                </div>
                
                <div class="grupo-formulario">
                    <label for="usuario">Usuario responsable:</label>
                    <select id="usuario" name="usuario" required>
                        <option value="">Seleccione un usuario</option>
                        <?php 
                        if ($resultado_usuarios && $resultado_usuarios->num_rows > 0) {
                            while ($usuario = $resultado_usuarios->fetch_assoc()) {
                                echo '<option value="' . $usuario['usuario_id'] . '">' . 
                                     htmlspecialchars($usuario['usuario_nombre'] . ' ' . $usuario['usuario_apellido']) . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>No hay usuarios disponibles</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="grupo-formulario">
                    <label for="foto">Imagen del Producto:</label>
                    <input type="file" id="foto" name="foto" accept="image/*">
                    <small>Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
                </div>
                
                <div class="acciones-formulario">
                    <a href="producto_listar.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
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