<?php
/**
 * SISTEMA DE FACTURACIÓN
 * 
 * Este archivo maneja la creación de facturas en el sistema de inventario.
 * Permite agregar productos a una factura temporal (en sesión) y luego
 * finalizar la venta guardando los datos en la base de datos.
 * 
 * Funcionalidades:
 * - Agregar productos a la factura
 * - Calcular totales con impuestos
 * - Finalizar venta y guardar en BD
 * - Cancelar factura
 * - Generar PDF de la factura
 */

// Inicia la sesión para mantener los productos de la factura
// Incluir archivos de configuración
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/auth.php'; // Agregar verificación de autenticación

// Obtener información del usuario logueado
$usuario_actual = getUsuarioLogueado();
$nombre_completo = getNombreCompleto();

// --- LÓGICA DE PROCESAMIENTO DE FORMULARIOS ---

// Verificamos si se envió una acción desde un formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ACCIÓN 1: Agregar un producto a la factura
    if (isset($_POST['accion']) && $_POST['accion'] == 'agregar') {
        
        // Validar y sanitizar datos de entrada
        $id_producto = filter_var($_POST['producto_id'], FILTER_VALIDATE_INT);
        $cantidad = filter_var($_POST['cantidad'], FILTER_VALIDATE_INT);
        
        // Verificar que los datos sean válidos
        if ($id_producto && $cantidad && $cantidad > 0) {
            
            // Impuesto fijo del 19% (se puede hacer configurable)
            $impuesto = 19;
            
            // Buscar los datos del producto en la base de datos
            $stmt = $conexion->prepare("SELECT producto_id, producto_nombre, producto_precio, producto_stock FROM producto WHERE producto_id = ?");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $producto = $resultado->fetch_assoc();

            // Verificar si el producto existe y hay stock suficiente
            if ($producto && $cantidad <= $producto['producto_stock']) {
                
                // Crear array con la información del producto para la factura
                $item = [
                    "id" => $producto['producto_id'],
                    "nombre" => $producto['producto_nombre'],
                    "precio" => $producto['producto_precio'],
                    "cantidad" => $cantidad,
                    "impuesto" => $impuesto
                ];

                // Inicializar array de factura si no existe
                if (!isset($_SESSION['factura_items'])) {
                    $_SESSION['factura_items'] = [];
                }

                // Agregar el item a la sesión
                $_SESSION['factura_items'][] = $item;
                
                // Mensaje de éxito
                $mensaje_exito = "Producto agregado correctamente.";
                
            } else {
                $mensaje_error = "No hay stock suficiente para este producto o el producto no existe.";
            }
        } else {
            $mensaje_error = "Datos de entrada inválidos.";
        }
    }

    // ACCIÓN 2: Finalizar la venta y guardar en la base de datos
    if (isset($_POST['accion']) && $_POST['accion'] == 'finalizar') {
        
        // Validar identificación del cliente
        $identificacion_cliente = trim($_POST['identificacion_cliente']);
        
        // Verificar que haya datos válidos
        if (!empty($identificacion_cliente) && !empty($_SESSION['factura_items'])) {
            
            try {
                // Iniciar transacción para asegurar integridad de datos
                $conexion->begin_transaction();
                
                $total_factura = 0;
                
                // Calcular el total de la factura
                foreach ($_SESSION['factura_items'] as $item) {
                    $subtotal = $item['precio'] * $item['cantidad'];
                    $total_factura += $subtotal + ($subtotal * ($item['impuesto'] / 100));
                }

                // 1. Insertar la factura en la tabla 'facturas'
                $stmt = $conexion->prepare("INSERT INTO facturas (identificacion_cliente, total) VALUES (?, ?)");
                $stmt->bind_param("sd", $identificacion_cliente, $total_factura);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al crear la factura: " . $stmt->error);
                }
                
                $id_factura = $conexion->insert_id; // Obtener el ID de la factura recién creada

                // 2. Insertar cada producto en 'detalle_factura' y actualizar el stock
                foreach ($_SESSION['factura_items'] as $item) {
                    
                    // Insertar detalle de la factura
                    $stmt_detalle = $conexion->prepare("INSERT INTO detalle_factura (id_factura, id_producto, cantidad, precio_unitario, impuesto) VALUES (?, ?, ?, ?, ?)");
                    $stmt_detalle->bind_param("iiidd", $id_factura, $item['id'], $item['cantidad'], $item['precio'], $item['impuesto']);
                    
                    if (!$stmt_detalle->execute()) {
                        throw new Exception("Error al guardar detalle de factura: " . $stmt_detalle->error);
                    }

                    // Actualizar stock del producto
                    $stmt_stock = $conexion->prepare("UPDATE producto SET producto_stock = producto_stock - ? WHERE producto_id = ?");
                    $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
                    
                    if (!$stmt_stock->execute()) {
                        throw new Exception("Error al actualizar stock: " . $stmt_stock->error);
                    }
                }

                // Confirmar transacción
                $conexion->commit();
                
                // Guardar el ID de factura en sesión para mostrar mensaje de confirmación
                $_SESSION['factura_generada'] = $id_factura;
                
                // Limpiar la sesión de items y redirigir
                unset($_SESSION['factura_items']);
                header("Location: factura.php?success=1");
                exit();

            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $conexion->rollback();
                $mensaje_error = "Error al procesar la factura: " . $e->getMessage();
            }

        } else {
            $mensaje_error = "Asegúrate de ingresar la identificación del cliente y agregar al menos un producto.";
        }
    }
    
    // ACCIÓN 3: Cancelar la factura
    if (isset($_POST['accion']) && $_POST['accion'] == 'cancelar') {
        unset($_SESSION['factura_items']);
        $mensaje_exito = "Factura cancelada correctamente.";
    }

    // ACCIÓN 4: Eliminar un producto específico de la factura
    if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar_item') {
        $item_index = intval($_POST['item_index']);
        if (isset($_SESSION['factura_items'][$item_index])) {
            unset($_SESSION['factura_items'][$item_index]);
            $_SESSION['factura_items'] = array_values($_SESSION['factura_items']); // Reindexar
            $mensaje_exito = "Producto eliminado de la factura.";
        }
    }
}

// Obtener todos los productos disponibles para el menú desplegable
// Solo productos con stock mayor a 0
$productos_disponibles = $conexion->query("SELECT producto_id, producto_nombre, producto_stock, producto_precio FROM producto WHERE producto_stock > 0 ORDER BY producto_nombre");

if (!$productos_disponibles) {
    $mensaje_error = "Error al cargar productos: " . $conexion->error;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Sistema de Facturación - Nueva Factura - <?php echo APP_NAME; ?></title>
    <!-- CSS del dashboard mejorado -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <!-- CSS de estilos generales -->
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        /* Ajustes específicos para la página de facturación */
        .dashboard-container {
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        
        .main-content {
            padding-top: 0;
        }
        
        .facturacion-header {
            background-color: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .facturacion-content {
            padding: 30px;
        }
        
        /* Tarjetas del formulario */
        .form-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .form-card-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .form-card-header h3 {
            color: #2c3e50;
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        /* Formulario de agregar productos */
        .add-product-form {
            display: grid;
            grid-template-columns: 1fr 150px 120px;
            gap: 15px;
            align-items: end;
        }
        
        /* Estilos para los campos de formulario */
        .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #2980b9;
            outline: none;
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.1);
        }
        
        /* Tabla de productos en la factura */
        .factura-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .factura-table th,
        .factura-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .factura-table th {
            background-color: #2c3e50;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .factura-table td {
            font-size: 14px;
        }
        
        .factura-table tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Totales */
        .totales-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .total-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 18px;
            color: #27ae60;
            border-top: 2px solid #27ae60;
            padding-top: 15px;
            margin-top: 10px;
        }
        
        /* Botones de acción */
        .actions-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            gap: 20px;
        }
        
        .finalizar-form {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .finalizar-form input {
            width: 250px;
        }
        
        /* Estado vacío */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-cart-icon {
            font-size: 72px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Mensajes de alerta */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-info {
            background-color: #cce7ff;
            border-color: #99d3ff;
            color: #0c5460;
        }
        
        .alert-dismissible {
            position: relative;
            padding-right: 50px;
        }
        
        .btn-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.7;
        }
        
        .btn-close:hover {
            opacity: 1;
        }
        
        /* Botón de eliminar producto */
        .btn-remove {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-remove:hover {
            background-color: #c82333;
        }
        
        /* Información adicional */
        .info-panel {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .info-panel h4 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-panel p {
            color: #0d47a1;
            margin: 0;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .facturacion-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .facturacion-content {
                padding: 20px;
            }
            
            .add-product-form {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .actions-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .finalizar-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .finalizar-form input {
                width: 100%;
            }
            
            .factura-table {
                font-size: 12px;
            }
            
            .factura-table th,
            .factura-table td {
                padding: 8px 10px;
            }
        }
        
        /* Animaciones */
        .form-card {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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
                <a href="dashboard.php" class="nav-item" title="Dashboard">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="views/productos/producto_listar.php" class="nav-item" title="Productos">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Productos</span>
                </a>
                <a href="facturas_listar.php" class="nav-item" title="Facturación">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-text">Facturación</span>
                </a>
                <a href="en_construccion.php?modulo=configuraciones" class="nav-item" title="Configuraciones">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Configuraciones</span>
                </a>
                <a href="views/usuarios/usuario_listar.php" class="nav-item" title="Usuarios">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Usuarios</span>
                </a>
                <a href="en_construccion.php?modulo=proveedores" class="nav-item" title="Proveedores">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="en_construccion.php?modulo=pedidos" class="nav-item" title="Pedidos">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Pedidos</span>
                </a>
                <a href="views/categorias/categoria_listar.php" class="nav-item" title="Categorías">
                    <span class="nav-icon">📁</span>
                    <span class="nav-text">Categoría</span>
                </a>
                <a href="en_construccion.php?modulo=informes" class="nav-item" title="Informes">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Informes</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Header de la página -->
            <div class="facturacion-header">
                <div>
                    <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 5px;">
                        <span style="margin-right: 10px;">🧾</span>Nueva Factura
                    </h1>
                    <p style="color: #6c757d; font-size: 16px;">Crea una nueva factura de venta</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="facturas_listar.php" class="btn btn-secondary">
                        <span style="margin-right: 5px;">📋</span> Ver Facturas
                    </a>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="facturacion-content">
                <!-- Mensajes de alerta -->
                <?php if (isset($mensaje_error)): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($mensaje_error); ?>
                        <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($mensaje_exito)): ?>
                    <div class="alert alert-success alert-dismissible">
                        <strong>✅ Éxito:</strong> <?php echo htmlspecialchars($mensaje_exito); ?>
                        <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php endif; ?>
                
                <!-- Mensaje de confirmación de factura generada -->
                <?php if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_SESSION['factura_generada'])): ?>
                    <div class="alert alert-info alert-dismissible">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>🎉 ¡Factura generada exitosamente!</strong><br>
                                La factura #<?php echo str_pad($_SESSION['factura_generada'], 6, '0', STR_PAD_LEFT); ?> ha sido creada y guardada en la base de datos.
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <a href="generar_pdf.php?id_factura=<?php echo $_SESSION['factura_generada']; ?>" 
                                   class="btn btn-primary btn-small">
                                    📄 Descargar PDF
                                </a>
                            </div>
                        </div>
                        <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                    <?php unset($_SESSION['factura_generada']); ?>
                <?php endif; ?>

                <!-- Información de ayuda -->
                <div class="info-panel">
                    <h4>💡 Información sobre la facturación</h4>
                    <p>Agrega productos a tu factura, verifica las cantidades y el cliente antes de finalizar. El sistema aplicará automáticamente el 19% de IVA a todos los productos.</p>
                </div>

                <!-- Formulario para agregar productos -->
                <div class="form-card">
                    <div class="form-card-header">
                        <h3>➕ Agregar Producto a la Factura</h3>
                    </div>
                    
                    <form action="factura.php" method="post">
                        <input type="hidden" name="accion" value="agregar">
                        <div class="add-product-form">
                            <div class="form-group">
                                <label for="producto_id" class="form-label">Producto</label>
                                <select name="producto_id" id="producto_id" class="form-control" required>
                                    <option value="">Selecciona un producto</option>
                                    <?php if ($productos_disponibles): ?>
                                        <?php while ($producto = $productos_disponibles->fetch_assoc()): ?>
                                            <option value="<?php echo $producto['producto_id']; ?>" 
                                                    data-precio="<?php echo $producto['producto_precio']; ?>"
                                                    data-stock="<?php echo $producto['producto_stock']; ?>">
                                                <?php echo htmlspecialchars($producto['producto_nombre']) . 
                                                      " - Stock: " . $producto['producto_stock'] . 
                                                      " - $" . number_format($producto['producto_precio'], 2); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="cantidad" class="form-label">Cantidad</label>
                                <input type="number" 
                                       name="cantidad" 
                                       id="cantidad" 
                                       value="1" 
                                       min="1" 
                                       class="form-control" 
                                       required>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    ➕ Agregar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Resumen de la factura -->
                <div class="form-card">
                    <div class="form-card-header">
                        <h3>🧾 Resumen de la Factura</h3>
                    </div>
                    
                    <?php if (empty($_SESSION['factura_items'])): ?>
                        <div class="empty-cart">
                            <div class="empty-cart-icon">🛒</div>
                            <h4 style="color: #6c757d; margin-bottom: 10px;">Factura vacía</h4>
                            <p style="color: #6c757d;">Aún no has agregado productos a esta factura.</p>
                        </div>
                    <?php else: ?>
                        <table class="factura-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th style="text-align: center;">Cantidad</th>
                                    <th style="text-align: right;">Precio Unit.</th>
                                    <th style="text-align: right;">Impuesto (19%)</th>
                                    <th style="text-align: right;">Subtotal</th>
                                    <th style="text-align: center;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_final = 0;
                                foreach ($_SESSION['factura_items'] as $index => $item): 
                                    $subtotal = $item['precio'] * $item['cantidad'];
                                    $impuesto_valor = $subtotal * ($item['impuesto'] / 100);
                                    $subtotal_con_impuesto = $subtotal + $impuesto_valor;
                                    $total_final += $subtotal_con_impuesto;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td style="text-align: center;"><?php echo $item['cantidad']; ?></td>
                                        <td style="text-align: right;">$<?php echo number_format($item['precio'], 2); ?></td>
                                        <td style="text-align: right;">$<?php echo number_format($impuesto_valor, 2); ?></td>
                                        <td style="text-align: right;"><strong>$<?php echo number_format($subtotal_con_impuesto, 2); ?></strong></td>
                                        <td style="text-align: center;">
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="accion" value="eliminar_item">
                                                <input type="hidden" name="item_index" value="<?php echo $index; ?>">
                                                <button type="submit" class="btn-remove" 
                                                        onclick="return confirm('¿Eliminar este producto de la factura?');"
                                                        title="Eliminar producto">
                                                    🗑️
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Totales -->
                        <div class="totales-section">
                            <?php
                            $subtotal_sin_impuesto = 0;
                            $total_impuestos = 0;
                            
                            foreach ($_SESSION['factura_items'] as $item) {
                                $subtotal_item = $item['precio'] * $item['cantidad'];
                                $impuesto_item = $subtotal_item * ($item['impuesto'] / 100);
                                $subtotal_sin_impuesto += $subtotal_item;
                                $total_impuestos += $impuesto_item;
                            }
                            ?>
                            <div class="total-row">
                                <span>Subtotal (sin impuestos):</span>
                                <span>$<?php echo number_format($subtotal_sin_impuesto, 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>IVA (19%):</span>
                                <span>$<?php echo number_format($total_impuestos, 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>TOTAL:</span>
                                <span>$<?php echo number_format($total_final, 2); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Botones de acción -->
                <div class="actions-section">
                    <form action="factura.php" method="post" style="display: inline;">
                        <input type="hidden" name="accion" value="cancelar">
                        <button type="submit" class="btn btn-danger" 
                                onclick="return confirm('¿Estás seguro de que quieres cancelar la factura? Se perderán todos los productos agregados.');"
                                <?php echo empty($_SESSION['factura_items']) ? 'disabled' : ''; ?>>
                            🗑️ Cancelar Factura
                        </button>
                    </form>
                    
                    <?php if (!empty($_SESSION['factura_items'])): ?>
                        <form action="factura.php" method="post" class="finalizar-form">
                            <input type="hidden" name="accion" value="finalizar">
                            <div class="form-group">
                                <label for="identificacion_cliente" class="form-label">Identificación del Cliente</label>
                                <input type="text" 
                                       name="identificacion_cliente" 
                                       id="identificacion_cliente"
                                       class="form-control" 
                                       placeholder="Cédula, NIT o RUC del cliente" 
                                       required 
                                       pattern="[0-9A-Za-z-]{3,20}" 
                                       title="Ingresa una identificación válida (3-20 caracteres)">
                            </div>
                            <button type="submit" class="btn btn-success" style="padding: 10px 30px;">
                                💾 Finalizar y Generar Factura
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Script mejorado para el manejo del sidebar
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
                
                // Disparar un evento personalizado
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
            
            // Detectar swipe en móviles
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

        // Función para actualizar el máximo de cantidad basado en el stock
        document.getElementById('producto_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const cantidadInput = document.getElementById('cantidad');
            
            if (selectedOption.value) {
                const stock = parseInt(selectedOption.dataset.stock);
                cantidadInput.max = stock;
                cantidadInput.value = 1;
                
                if (stock === 0) {
                    cantidadInput.disabled = true;
                } else {
                    cantidadInput.disabled = false;
                }
            } else {
                cantidadInput.max = '';
                cantidadInput.disabled = false;
            }
        });

        // Validación de cantidad antes de enviar el formulario
        document.querySelector('form[action="factura.php"]').addEventListener('submit', function(e) {
            const productoSelect = document.getElementById('producto_id');
            const cantidadInput = document.getElementById('cantidad');
            const accionInput = document.querySelector('input[name="accion"]');
            
            if (accionInput.value === 'agregar') {
                const selectedOption = productoSelect.options[productoSelect.selectedIndex];
                
                if (selectedOption.value) {
                    const stock = parseInt(selectedOption.dataset.stock);
                    const cantidad = parseInt(cantidadInput.value);
                    
                    if (cantidad > stock) {
                        e.preventDefault();
                        alert(`No hay suficiente stock. Stock disponible: ${stock}`);
                        return false;
                    }
                }
            }
        });

        // Auto-ocultar mensajes después de 5 segundos
        document.querySelectorAll('.alert').forEach(function(alert) {
            setTimeout(function() {
                if (alert && alert.parentElement) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.3s ease';
                    setTimeout(function() {
                        if (alert && alert.parentElement) {
                            alert.style.display = 'none';
                        }
                    }, 300);
                }
            }, 5000);
        });

        // Enfocar el campo de identificación del cliente si la factura tiene productos
        <?php if (!empty($_SESSION['factura_items'])): ?>
            document.getElementById('identificacion_cliente')?.focus();
        <?php endif; ?>
    </script>
</body>
</html>