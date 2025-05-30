<?php
// Incluir archivos de configuración y conexión
// Nota: Ahora subimos dos niveles (../../) para llegar a la carpeta config
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}

// Configuración de paginación
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$categorias_por_pagina = ITEMS_POR_PAGINA;
$inicio = ($pagina_actual - 1) * $categorias_por_pagina;

// Obtener el total de categorías para la paginación
$sql_total = "SELECT COUNT(*) as total FROM categoria";
$resultado_total = $conexion->query($sql_total);
$total_categorias = $resultado_total->fetch_assoc()['total'];
$total_paginas = ceil($total_categorias / $categorias_por_pagina);

// Obtener categorías
$sql = "SELECT * FROM categoria ORDER BY categoria_nombre ASC LIMIT $inicio, $categorias_por_pagina";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Listado de Categorías - <?php echo APP_NAME; ?></title>
    <!-- Actualizamos la ruta del CSS -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Listado de Categorías</h1>
            <nav>
                <ul>
                    <!-- Actualizamos las rutas de navegación -->
                    <li><a href="../../index.php">Inicio</a></li>
                    <li><a href="../productos/producto_listar.php">Productos</a></li>
                    <li><a href="categoria_listar.php" class="active">Categorías</a></li>
                    <li><a href="../usuarios/usuario_listar.php">Usuarios</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="acciones-top">
            <h2>Administrar Categorías</h2>
            <a href="categoria_nuevo.php" class="btn btn-primary">Agregar Nueva Categoría</a>
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
                            <th>Nombre</th>
                            <th>Ubicación</th>
                            <th>Total Productos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($categoria = $resultado->fetch_assoc()): 
                            // Consultar cantidad de productos en esta categoría
                            $sql_productos = "SELECT COUNT(*) as total FROM producto WHERE categoria_id = " . $categoria['categoria_id'];
                            $resultado_productos = $conexion->query($sql_productos);
                            $total_productos = $resultado_productos ? $resultado_productos->fetch_assoc()['total'] : 0;
                        ?>
                            <tr>
                                <td><?php echo $categoria['categoria_id']; ?></td>
                                <td><?php echo htmlspecialchars($categoria['categoria_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($categoria['categoria_ubicacion']); ?></td>
                                <td><?php echo $total_productos; ?></td>
                                <td class="acciones">
                                    <a href="categoria_editar.php?id=<?php echo $categoria['categoria_id']; ?>" 
                                       class="btn-small">Editar</a>
                                    <a href="categoria_eliminar.php?id=<?php echo $categoria['categoria_id']; ?>" 
                                       class="btn-small btn-danger"
                                       onclick="return confirm('¿Está seguro que desea eliminar esta categoría?');">Eliminar</a>
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
                    No hay categorías registradas en el sistema.
                </div>
                <div class="acciones">
                    <a href="categoria_nuevo.php" class="btn btn-primary">Agregar la primera categoría</a>
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