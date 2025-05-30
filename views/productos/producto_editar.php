<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}

// Verificar que se recibió un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: producto_listar.php?mensaje=ID de producto no válido&tipo=error');
    exit;
}

$producto_id = intval($_GET['id']);

// Consultar datos del producto
$sql = "SELECT * FROM producto WHERE producto_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar si el producto existe
if ($resultado->num_rows === 0) {
    header('Location: producto_listar.php?mensaje=El producto no existe&tipo=error');
    exit;
}

// Obtener datos del producto
$producto = $resultado->fetch_assoc();

// Consultar las categorías para el select
$sql_categorias = "SELECT categoria_id, categoria_nombre FROM categoria ORDER BY categoria_nombre ASC";
$resultado_categorias = $conexion->query($sql_categorias);

// Consultar usuarios para el select
$sql_usuarios = "SELECT usuario_id, usuario_nombre, usuario_apellido FROM usuario ORDER BY usuario_nombre ASC";
$resultado_usuarios = $conexion->query($sql_usuarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Editar Producto - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Editar Producto</h1>
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
            <h2>Editar Producto #<?php echo $producto_id; ?></h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="mensaje error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- El formulario apunta al controlador producto_actualizar.php -->
            <form action="../../controllers/producto_actualizar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
                
                <div class="grupo-formulario">
                    <label for="codigo">Código del Producto:</label>
                    <input type="text" id="codigo" name="codigo" maxlength="70" required
                           value="<?php echo htmlspecialchars($producto['producto_codigo']); ?>">
                </div>
                
                <div class="grupo-formulario">
                    <label for="nombre">Nombre del Producto:</label>
                    <input type="text" id="nombre" name="nombre" maxlength="70" required
                           value="<?php echo htmlspecialchars($producto['producto_nombre']); ?>">
                </div>
                
                <div class="grupo-formulario">
                    <label for="precio">Precio ($):</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required
                           value="<?php echo $producto['producto_precio']; ?>">
                </div>
                
                <div class="grupo-formulario">
                    <label for="stock">Stock (unidades):</label>
                    <input type="number" id="stock" name="stock" min="0" required
                           value="<?php echo $producto['producto_stock']; ?>">
                </div>
                
                <div class="grupo-formulario">
                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php 
                        if ($resultado_categorias && $resultado_categorias->num_rows > 0) {
                            while ($categoria = $resultado_categorias->fetch_assoc()) {
                                $selected = ($categoria['categoria_id'] == $producto['categoria_id']) ? 'selected' : '';
                                echo '<option value="' . $categoria['categoria_id'] . '" ' . $selected . '>' . 
                                     htmlspecialchars($categoria['categoria_nombre']) . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>No hay categorías disponibles</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="grupo-formulario">
                    <label for="usuario">Usuario responsable:</label>
                    <select id="usuario" name="usuario" required>
                        <option value="">Seleccione un usuario</option>
                        <?php 
                        if ($resultado_usuarios && $resultado_usuarios->num_rows > 0) {
                            while ($usuario = $resultado_usuarios->fetch_assoc()) {
                                $selected = ($usuario['usuario_id'] == $producto['usuario_id']) ? 'selected' : '';
                                echo '<option value="' . $usuario['usuario_id'] . '" ' . $selected . '>' . 
                                     htmlspecialchars($usuario['usuario_nombre'] . ' ' . $usuario['usuario_apellido']) . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>No hay usuarios disponibles</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="grupo-formulario">
                    <label>Imagen actual:</label>
                    <div class="imagen-actual">
                        <?php 
                        // Las imágenes están en la carpeta uploads en la raíz del proyecto
                        if (!empty($producto['producto_foto']) && file_exists('../../uploads/' . $producto['producto_foto'])): 
                        ?>
                            <img src="../../uploads/<?php echo $producto['producto_foto']; ?>" 
                                 alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>"
                                 width="100">
                            <input type="hidden" name="foto_actual" value="<?php echo $producto['producto_foto']; ?>">
                        <?php else: ?>
                            <p>No hay imagen disponible</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grupo-formulario">
                    <label for="foto">Cambiar imagen:</label>
                    <input type="file" id="foto" name="foto" accept="image/*">
                    <small>Deje en blanco para mantener la imagen actual. Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
                </div>
                
                <div class="acciones-formulario">
                    <a href="producto_listar.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Producto</button>
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