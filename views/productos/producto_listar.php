<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}

// Configuración de paginación
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$productos_por_pagina = ITEMS_POR_PAGINA;
$inicio = ($pagina_actual - 1) * $productos_por_pagina;

// Obtener el total de productos para la paginación
$sql_total = "SELECT COUNT(*) as total FROM producto";
$resultado_total = $conexion->query($sql_total);
$total_productos = $resultado_total->fetch_assoc()['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);

// Obtener productos con información de categoría
$sql = "SELECT p.*, c.categoria_nombre
        FROM producto p
        JOIN categoria c ON p.categoria_id = c.categoria_id
        ORDER BY p.producto_id DESC
        LIMIT $inicio, $productos_por_pagina";

$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Listado de Productos - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Listado de Productos</h1>
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
        <section class="acciones-top">
            <h2>Administrar Productos</h2>
            <a href="producto_nuevo.php" class="btn btn-primary">Agregar Nuevo Producto</a>
        </section>

        <?php if (isset($_GET['mensaje'])): ?>
            <div class="mensaje <?php echo $_GET['tipo'] ?? 'info'; ?>">
                <?php echo htmlspecialchars($_GET['mensaje']); ?>
            </div>
        <?php endif; ?>

        <section class="tabla-resultados">
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Categoría</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($producto = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $producto['producto_id']; ?></td>
                                <td><?php echo htmlspecialchars($producto['producto_codigo']); ?></td>
                                <td>
                                    <?php 
                                    // Nota: Las imágenes se mantienen en la carpeta uploads en la raíz
                                    // por lo que necesitamos subir dos niveles para acceder a ellas
                                    if (!empty($producto['producto_foto']) && file_exists('../../uploads/' . $producto['producto_foto'])): 
                                    ?>
                                        <img src="../../uploads/<?php echo $producto['producto_foto']; ?>" 
                                             alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>"
                                             width="50">
                                    <?php else: ?>
                                        <img src="../../assets/img/no-image.png" alt="Sin imagen" width="50">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($producto['producto_nombre']); ?></td>
                                <td>$<?php echo number_format($producto['producto_precio'], 2); ?></td>
                                <td><?php echo $producto['producto_stock']; ?></td>
                                <td><?php echo htmlspecialchars($producto['categoria_nombre']); ?></td>
                                <td class="acciones">
                                    <a href="producto_editar.php?id=<?php echo $producto['producto_id']; ?>" 
                                       class="btn-small">Editar</a>
                                    <a href="producto_eliminar.php?id=<?php echo $producto['producto_id']; ?>" 
                                       class="btn-small btn-danger"
                                       onclick="return confirm('¿Está seguro que desea eliminar este producto?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="paginacion">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="?pagina=<?php echo $pagina_actual - 1; ?>" class="btn-small">&laquo; Anterior</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <a href="?pagina=<?php echo $i; ?>" 
                               class="btn-small <?php echo ($pagina_actual == $i) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina_actual + 1; ?>" class="btn-small">Siguiente &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="mensaje info">
                    No hay productos registrados en el sistema.
                </div>
                <div class="acciones">
                    <a href="producto_nuevo.php" class="btn btn-primary">Agregar el primer producto</a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> - <?php echo APP_NAME; ?></p>
        </div>
    </footer>
</body>
</html>