<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    die('Error: No se ha podido conectar a la base de datos.');
}

// Obtener estadísticas
$sql_total_productos = "SELECT SUM(producto_stock) as total FROM producto";
$result = $conexion->query($sql_total_productos);
$total_inventario = $result->fetch_assoc()['total'] ?? 0;

$sql_productos_pendientes = "SELECT COUNT(*) as total FROM producto WHERE producto_stock < 10";
$result = $conexion->query($sql_productos_pendientes);
$productos_pendientes = $result->fetch_assoc()['total'] ?? 0;

$sql_total_categorias = "SELECT COUNT(*) as total FROM categoria";
$result = $conexion->query($sql_total_categorias);
$total_categorias = $result->fetch_assoc()['total'] ?? 0;

// Para proveedores (simulado por ahora)
$total_proveedores = 31;

// Obtener últimos productos
$sql_productos = "SELECT p.*, c.categoria_nombre 
                  FROM producto p
                  JOIN categoria c ON p.categoria_id = c.categoria_id
                  ORDER BY p.producto_id DESC LIMIT 5";
$productos = $conexion->query($sql_productos);

// Obtener categorías con cantidad de productos
$sql_categorias = "SELECT c.*, COUNT(p.producto_id) as total_productos
                   FROM categoria c
                   LEFT JOIN producto p ON c.categoria_id = p.categoria_id
                   GROUP BY c.categoria_id
                   ORDER BY c.categoria_nombre ASC";
$categorias = $conexion->query($sql_categorias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
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
                <a href="dashboard.php" class="nav-item active">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="views/productos/producto_listar.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Productos</span>
                </a>
                <a href="en_construccion.php" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Configuraciones</span>
                </a>
                <a href="views/usuarios/usuario_listar.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Usuarios</span>
                </a>
                <a href="en_construccion.php" class="nav-item">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="en_construccion.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Pedidos</span>
                </a>
                <a href="views/categorias/categoria_listar.php" class="nav-item">
                    <span class="nav-icon">📁</span>
                    <span class="nav-text">Categoría</span>
                </a>
                <a href="en_construccion.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Informes</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <h1>Hola Jorge 👋🏼</h1>
                    <p class="subtitle">Buen día</p>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar" class="search-input">
                        <span class="search-icon">🔍</span>
                    </div>
                    <div class="notifications">
                        <span class="notification-icon">🔔</span>
                    </div>
                    <div class="user-profile">
                        <img src="assets/img/user-avatar.php?name=Jorge+Padilla" alt="Jorge P." class="user-avatar">
                        <span class="user-name">Jorge P.</span>
                        <span class="dropdown-arrow">▼</span>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Product and Category Lists -->
                <div class="lists-section">
                    <!-- Products List -->
                    <div class="list-card">
                        <div class="list-header">
                            <h2>Lista de productos</h2>
                            <a href="views/productos/producto_listar.php" class="view-all">Ver todos</a>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Imagen</th>
                                    <th>Categoría</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($productos && $productos->num_rows > 0): ?>
                                    <?php while ($producto = $productos->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($producto['producto_nombre']); ?></td>
                                            <td>
                                                <?php if (!empty($producto['producto_foto']) && file_exists('uploads/' . $producto['producto_foto'])): ?>
                                                    <img src="uploads/<?php echo $producto['producto_foto']; ?>" alt="Producto" class="product-thumb">
                                                <?php else: ?>
                                                    <div class="placeholder-img">📷</div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($producto['categoria_nombre']); ?></td>
                                            <td><?php echo $producto['producto_stock']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No hay productos registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Categories List -->
                    <div class="list-card">
                        <div class="list-header">
                            <h2>Lista de categorías</h2>
                            <a href="views/categorias/categoria_listar.php" class="view-all">Ver todas</a>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Image</th>
                                    <th>Ubicación</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($categorias && $categorias->num_rows > 0): ?>
                                    <?php while ($categoria = $categorias->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($categoria['categoria_nombre']); ?></td>
                                            <td><div class="placeholder-img">📷</div></td>
                                            <td><?php echo htmlspecialchars($categoria['categoria_ubicacion']); ?></td>
                                            <td><?php echo $categoria['total_productos']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No hay categorías registradas</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Statistics Section -->
                <div class="stats-section">
                    <!-- Products Stats -->
                    <div class="stats-card">
                        <h3>Resumen de productos</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-icon orange">📦</div>
                                <div class="stat-value"><?php echo number_format($total_inventario); ?></div>
                                <div class="stat-label">Cantidad en inventario</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon purple">⏱️</div>
                                <div class="stat-value"><?php echo $productos_pendientes; ?></div>
                                <div class="stat-label">Pendiente por recibir</div>
                            </div>
                        </div>
                    </div>

                    <!-- Providers Stats -->
                    <div class="stats-card">
                        <h3>Resumen de proveedores</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-icon blue">👥</div>
                                <div class="stat-value"><?php echo $total_proveedores; ?></div>
                                <div class="stat-label">Número de proveedores</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon purple">📂</div>
                                <div class="stat-value"><?php echo $total_categorias; ?></div>
                                <div class="stat-label">Número de categorías</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
            
            // Guardar estado en localStorage
            const isCollapsed = document.getElementById('sidebar').classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });

        // Restaurar estado del sidebar
        window.addEventListener('DOMContentLoaded', function() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.querySelector('.main-content').classList.add('expanded');
            }
        });
    </script>
</body>
</html>