<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/auth.php'; // Agregar verificación de autenticación

// Obtener información del usuario logueado
$usuario_actual = getUsuarioLogueado();
$nombre_completo = getNombreCompleto();

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
                <a href="facturas_listar.php" class="nav-item">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-text">Facturación</span>
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
                    <h1>Hola <?php echo $nombre_completo; ?> 👋🏼</h1>
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
                    <div class="user-profile" id="userProfile">
                        <img src="assets/img/user-avatar.php?name=<?php echo urlencode($nombre_completo); ?>" alt="<?php echo htmlspecialchars($nombre_completo); ?>" class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars($nombre_completo); ?></span>
                        <span class="dropdown-arrow">▼</span>
                        
                        <!-- Menú desplegable del usuario -->
                        <div class="user-dropdown" id="userDropdown">
                            <div class="dropdown-header">
                                <img src="assets/img/user-avatar.php?name=<?php echo urlencode($nombre_completo); ?>" alt="<?php echo htmlspecialchars($nombre_completo); ?>" class="dropdown-avatar">
                                <div class="dropdown-user-info">
                                    <span class="dropdown-name"><?php echo htmlspecialchars($nombre_completo); ?></span>
                                    <span class="dropdown-email"><?php echo htmlspecialchars($usuario_actual['email']); ?></span>
                                </div>
                            </div>
                            <div class="dropdown-menu">
                                <a href="views/usuarios/usuario_editar.php?id=<?php echo $usuario_actual['id']; ?>" class="dropdown-item">
                                    <span class="dropdown-icon">👤</span>
                                    Mi Perfil
                                </a>
                                <a href="controllers/logout.php" class="dropdown-item">
                                    <span class="dropdown-icon">🚪</span>
                                    Cerrar Sesión
                                </a>
                            </div>
                        </div>
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
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    // Función para alternar el sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        // Actualizar la clase del contenido principal
        if (sidebar.classList.contains('collapsed')) {
            mainContent.classList.add('expanded');
        } else {
            mainContent.classList.remove('expanded');
        }
        // Guardar el estado en localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
        // Disparar un evento personalizado para que otros componentes puedan reaccionar
        window.dispatchEvent(new Event('sidebarToggled'));
    }
    // Event listener para el botón de toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    // Restaurar el estado del sidebar desde localStorage
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
    }
    // Manejo del sidebar en dispositivos móviles
    let touchStartX = 0;
    let touchEndX = 0;
    // Detectar swipe en móviles para abrir/cerrar el sidebar
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    function handleSwipe() {
        // Swipe derecha para abrir
        if (touchEndX > touchStartX + 50 && sidebar.classList.contains('collapsed')) {
            toggleSidebar();
        }
        // Swipe izquierda para cerrar
        if (touchEndX < touchStartX - 50 && !sidebar.classList.contains('collapsed')) {
            toggleSidebar();
        }
    }
    // Cerrar sidebar al hacer clic fuera en móviles
    if (window.innerWidth <= 768) {
        document.addEventListener('click', function(e) {
            const isClickInsideSidebar = sidebar.contains(e.target);
            const isClickOnToggle = sidebarToggle.contains(e.target);
            if (!isClickInsideSidebar && !isClickOnToggle && !sidebar.classList.contains('collapsed')) {
                toggleSidebar();
            }
        });
    }
    // Ajustar el sidebar según el tamaño de la ventana
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        }, 250);
    });
});

// Función para actualizar el elemento activo del menú
function setActiveMenuItem() {
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.classList.remove('active');
        const href = item.getAttribute('href');
        if (href && currentPath.includes(href.replace('../', '').replace('../../', ''))) {
            item.classList.add('active');
        }
    });
}

// Ejecutar al cargar la página
setActiveMenuItem();

        // Menú desplegable del usuario
        const userProfile = document.getElementById('userProfile');
        const userDropdown = document.getElementById('userDropdown');

        if (userProfile && userDropdown) {
            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
            });

            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (!userProfile.contains(e.target)) {
                    userDropdown.classList.remove('active');
                }
            });

            // Cerrar menú con ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    userDropdown.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>