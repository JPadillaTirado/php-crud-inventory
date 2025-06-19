<?php
// Incluir archivos de configuración y conexión
// Desde views/usuarios/ necesitamos subir dos niveles para llegar a config/
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}

// Configuración de paginación
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$usuarios_por_pagina = ITEMS_POR_PAGINA;
$inicio = ($pagina_actual - 1) * $usuarios_por_pagina;

// Obtener el total de usuarios para la paginación
$sql_total = "SELECT COUNT(*) as total FROM usuario";
$resultado_total = $conexion->query($sql_total);
$total_usuarios = $resultado_total->fetch_assoc()['total'];
$total_paginas = ceil($total_usuarios / $usuarios_por_pagina);

// Obtener usuarios
$sql = "SELECT * FROM usuario ORDER BY usuario_nombre ASC LIMIT $inicio, $usuarios_por_pagina";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Listado de Usuarios - <?php echo APP_NAME; ?></title>
    <!-- CSS ahora está en assets/css/ -->
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Listado de Usuarios</h1>
            <nav>
                <ul>
                    <!-- Enlaces actualizados para la nueva estructura -->
                    <li><a href="../../index.php">Inicio</a></li>
                    <li><a href="../productos/producto_listar.php">Productos</a></li>
                    <li><a href="../categorias/categoria_listar.php">Categorías</a></li>
                    <li><a href="usuario_listar.php" class="active">Usuarios</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="acciones-top">
            <h2>Administrar Usuarios</h2>
            <a href="usuario_nuevo.php" class="btn btn-primary">Agregar Nuevo Usuario</a>
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
                            <th>Apellido</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Productos Registrados</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usuario = $resultado->fetch_assoc()): 
                            // Consultar cantidad de productos registrados por este usuario
                            $sql_productos = "SELECT COUNT(*) as total FROM producto WHERE usuario_id = " . $usuario['usuario_id'];
                            $resultado_productos = $conexion->query($sql_productos);
                            $total_productos = $resultado_productos ? $resultado_productos->fetch_assoc()['total'] : 0;
                        ?>
                            <tr>
                                <td><?php echo $usuario['usuario_id']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['usuario_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['usuario_apellido']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['usuario_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['usuario_email']); ?></td>
                                <td><?php echo $total_productos; ?></td>
                                <td class="acciones">
                                    <a href="usuario_editar.php?id=<?php echo $usuario['usuario_id']; ?>" 
                                       class="btn-small">Editar</a>
                                    <a href="usuario_eliminar.php?id=<?php echo $usuario['usuario_id']; ?>" 
                                       class="btn-small btn-danger"
                                       onclick="return confirm('¿Está seguro que desea eliminar este usuario?');">Eliminar</a>
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
                    No hay usuarios registrados en el sistema.
                </div>
                <div class="acciones">
                    <a href="usuario_nuevo.php" class="btn btn-primary">Agregar el primer usuario</a>
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