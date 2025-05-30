<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';

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
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><?php echo APP_NAME; ?></h1>
            <nav>
                <ul>
                    <li><a href="index.php" class="active">Inicio</a></li>
                    <li><a href="views/productos/producto_listar.php">Productos</a></li>
                    <li><a href="views/categorias/categoria_listar.php">Categorías</a></li>
                    <li><a href="views/usuarios/usuario_listar.php">Usuarios</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="bienvenida">
            <h2>Bienvenido al Sistema de Inventario</h2>
            <p>Seleccione una opción del menú para comenzar a gestionar sus productos.</p>
        </section>

        <section class="resumen">
            <h3>Resumen del Inventario</h3>
            
            <?php
            // Obtener conteo de productos
            $sql_productos = "SELECT COUNT(*) as total FROM producto";
            $result_productos = $conexion->query($sql_productos);
            $total_productos = ($result_productos) ? $result_productos->fetch_assoc()['total'] : 0;
            
            // Obtener conteo de categorías
            $sql_categorias = "SELECT COUNT(*) as total FROM categoria";
            $result_categorias = $conexion->query($sql_categorias);
            $total_categorias = ($result_categorias) ? $result_categorias->fetch_assoc()['total'] : 0;
            
            // Mostrar resumen
            ?>
            
            <div class="stats">
                <div class="stat-box">
                    <h4>Productos</h4>
                    <p class="big-number"><?php echo $total_productos; ?></p>
                    <a href="views/productos/producto_listar.php" class="btn">Ver Productos</a>
                </div>
                
                <div class="stat-box">
                    <h4>Categorías</h4>
                    <p class="big-number"><?php echo $total_categorias; ?></p>
                    <a href="views/categorias/categoria_listar.php" class="btn">Ver Categorías</a>
                </div>
            </div>
        </section>
        
        <section class="ultimos-productos">
            <h3>Últimos Productos Agregados</h3>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Categoría</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Consultar los últimos 5 productos agregados
                    $sql = "SELECT p.*, c.categoria_nombre 
                            FROM producto p
                            JOIN categoria c ON p.categoria_id = c.categoria_id
                            ORDER BY p.producto_id DESC LIMIT 5";
                    
                    $resultado = $conexion->query($sql);
                    
                    if ($resultado && $resultado->num_rows > 0) {
                        while ($producto = $resultado->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$producto['producto_codigo']}</td>";
                            echo "<td>{$producto['producto_nombre']}</td>";
                            echo "<td>$" . number_format($producto['producto_precio'], 2) . "</td>";
                            echo "<td>{$producto['producto_stock']}</td>";
                            echo "<td>{$producto['categoria_nombre']}</td>";
                            echo "<td>";
                            echo "<a href='producto_editar.php?id={$producto['producto_id']}' class='btn-small'>Editar</a> ";
                            echo "<a href='producto_eliminar.php?id={$producto['producto_id']}' class='btn-small btn-danger'>Eliminar</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No hay productos registrados</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div class="acciones">
                <a href="producto_nuevo.php" class="btn btn-primary">Agregar Nuevo Producto</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> - <?php echo APP_NAME; ?></p>
        </div>
    </footer>
</body>
</html>