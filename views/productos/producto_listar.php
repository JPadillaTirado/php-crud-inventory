<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

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

// === INICIO LÓGICA FACTURACIÓN ===
if (isset($_GET['seccion']) && $_GET['seccion'] === 'factura') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($conexion)) {
        require_once __DIR__ . '/../../config/conexion.php';
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['accion']) && $_POST['accion'] == 'agregar') {
            $id_producto = $_POST['producto_id'];
            $cantidad = $_POST['cantidad'];
            $impuesto = 19; // Impuesto fijo
            $stmt = $conexion->prepare("SELECT producto_nombre as nombre, producto_precio as precio, producto_stock as stock FROM producto WHERE producto_id = ?");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $producto = $resultado->fetch_assoc();
            if ($producto && $cantidad <= $producto['stock']) {
                $item = [
                    "id" => $id_producto,
                    "nombre" => $producto['nombre'],
                    "precio" => $producto['precio'],
                    "cantidad" => $cantidad,
                    "impuesto" => $impuesto
                ];
                $_SESSION['factura_items'][] = $item;
            } else {
                echo "<script>alert('No hay stock suficiente para este producto.');</script>";
            }
        }
        if (isset($_POST['accion']) && $_POST['accion'] == 'finalizar') {
            $identificacion_cliente = $_POST['identificacion_cliente'];
            if (!empty($identificacion_cliente) && !empty($_SESSION['factura_items'])) {
                $total_factura = 0;
                foreach ($_SESSION['factura_items'] as $item) {
                    $subtotal = $item['precio'] * $item['cantidad'];
                    $total_factura += $subtotal + ($subtotal * ($item['impuesto'] / 100));
                }
                $stmt = $conexion->prepare("INSERT INTO facturas (identificacion_cliente, total) VALUES (?, ?)");
                $stmt->bind_param("sd", $identificacion_cliente, $total_factura);
                $stmt->execute();
                $id_factura = $conexion->insert_id;
                foreach ($_SESSION['factura_items'] as $item) {
                    $stmt_detalle = $conexion->prepare("INSERT INTO detalle_factura (id_factura, id_producto, cantidad, precio_unitario, impuesto) VALUES (?, ?, ?, ?, ?)");
                    $stmt_detalle->bind_param("iiidd", $id_factura, $item['id'], $item['cantidad'], $item['precio'], $item['impuesto']);
                    $stmt_detalle->execute();
                    $stmt_stock = $conexion->prepare("UPDATE producto SET producto_stock = producto_stock - ? WHERE producto_id = ?");
                    $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
                    $stmt_stock->execute();
                }
                unset($_SESSION['factura_items']);
                header("Location: ../../generar_pdf.php?id_factura=" . $id_factura);
                exit();
            } else {
                echo "<script>alert('Asegúrate de ingresar la identificación del cliente y agregar al menos un producto.');</script>";
            }
        }
        if (isset($_POST['accion']) && $_POST['accion'] == 'cancelar') {
            unset($_SESSION['factura_items']);
        }
    }
    $productos_disponibles = $conexion->query("SELECT producto_id as id, producto_nombre as nombre, producto_stock as stock FROM producto WHERE producto_stock > 0");
}
// === FIN LÓGICA FACTURACIÓN ===
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Listado de Productos - <?php echo APP_NAME; ?></title>
    <!-- Incluimos tanto el CSS del dashboard como el de estilos generales -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de productos */
        .dashboard-container {
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        
        .main-content {
            padding-top: 0;
        }
        
        .productos-header {
            background-color: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .productos-content {
            padding: 30px;
        }
        
        /* Ajustes para la tabla */
        .tabla-resultados {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        /* Ajuste para los mensajes */
        .mensaje {
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .productos-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .productos-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-icon">📦</span>
                    <span class="logo-text">Inventory</span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">☰</button>
            </div>
            
            <nav class="sidebar-nav">
                <a href="../../dashboard.php" class="nav-item" title="Dashboard">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="producto_listar.php" class="nav-item active" title="Productos">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Productos</span>
                </a>
                <a href="../../facturas_listar.php" class="nav-item" title="Facturación">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-text">Facturación</span>
                </a>
                <a href="../../en_construccion.php?modulo=configuraciones" class="nav-item" title="Configuraciones">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Configuraciones</span>
                </a>
                <a href="../usuarios/usuario_listar.php" class="nav-item" title="Usuarios">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Usuarios</span>
                </a>
                <a href="../../en_construccion.php?modulo=proveedores" class="nav-item" title="Proveedores">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="../../en_construccion.php?modulo=Compras" class="nav-item" title="Compras">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Compras</span>
                </a>
                <a href="../categorias/categoria_listar.php" class="nav-item" title="Categorías">
                    <span class="nav-icon">📁</span>
                    <span class="nav-text">Categoría</span>
                </a>
                <a href="../../en_construccion.php?modulo=informes" class="nav-item" title="Informes">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Informes</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Header de la página -->
            <div class="productos-header">
                <div>
                    <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">Listado de Productos</h1>
                    <p style="color: #6c757d; font-size: 16px;">Administra tu inventario de productos</p>
                </div>
                <a href="producto_nuevo.php" class="btn btn-primary">Agregar Nuevo Producto</a>
            </div>

            <!-- Contenido principal -->
            <div class="productos-content">
                <?php if (isset($_GET['seccion']) && $_GET['seccion'] === 'factura'): ?>
                    <iframe src="../../factura.php" style="width:100%;min-height:800px;border:none;background:white"></iframe>
                <?php else: ?>
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
                        <div class="acciones" style="margin-top: 20px; text-align: center;">
                            <a href="producto_nuevo.php" class="btn btn-primary">Agregar el primer producto</a>
                        </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('expanded');
            
            // Guardar estado en localStorage
            const isCollapsed = document.getElementById('sidebar').classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });

        // Restaurar estado del sidebar
        window.addEventListener('DOMContentLoaded', function() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('mainContent').classList.add('expanded');
            }
        });
    </script>
</body>
</html>