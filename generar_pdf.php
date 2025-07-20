<?php
/**
 * GENERADOR DE PDF PARA FACTURAS
 * 
 * Este archivo genera un PDF de factura usando la biblioteca FPDF.
 * Recibe el ID de factura por GET y genera un documento profesional
 * con todos los detalles de la venta.
 * 
 * Funcionalidades:
 * - Genera PDF con encabezado y pie de página personalizados
 * - Muestra información completa de la factura
 * - Lista todos los productos con precios e impuestos
 * - Calcula totales automáticamente
 * - Descarga automática del PDF
 */

// Configuración de FPDF
define('FPDF_FONTPATH', __DIR__ . '/fpdf/font/');

// Incluir archivos necesarios
require_once __DIR__ . '/fpdf/fpdf.php';
require_once __DIR__ . '/config/conexion.php';

/**
 * Clase PDF personalizada que extiende FPDF
 * Permite crear encabezados y pies de página personalizados
 */
class PDF extends FPDF
{
    /**
     * Encabezado de página
     * Se ejecuta automáticamente en cada página nueva
     */
    function Header()
    {
        // Logo de la empresa (opcional)
        // $this->Image('logo.png', 10, 6, 30);
        
        // Título principal
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0, 10, 'FACTURA DE VENTA', 0, 1, 'C');
        
        // Línea separadora
        $this->SetDrawColor(0, 0, 0);
        $this->Line(10, 25, 200, 25);
        $this->Ln(15);
    }

    /**
     * Pie de página
     * Se ejecuta automáticamente al final de cada página
     */
    function Footer()
    {
        // Posición a 1.5 cm del final
        $this->SetY(-15);
        
        // Línea separadora
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        
        // Información del pie de página
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->Cell(0, 10, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 0, 'R');
    }
}

// --- VALIDACIÓN Y OBTENCIÓN DE DATOS ---

// Verificar que se haya pasado un ID de factura válido
if (!isset($_GET['id_factura']) || !is_numeric($_GET['id_factura'])) {
    die("Error: ID de factura inválido o no proporcionado.");
}

$id_factura = (int)$_GET['id_factura'];

try {
    // Consultar los datos principales de la factura
    $stmt_factura = $conexion->prepare("
        SELECT id, identificacion_cliente, fecha, total 
        FROM facturas 
        WHERE id = ?
    ");
    
    if (!$stmt_factura) {
        throw new Exception("Error en la consulta de factura: " . $conexion->error);
    }
    
    $stmt_factura->bind_param("i", $id_factura);
    $stmt_factura->execute();
    $resultado_factura = $stmt_factura->get_result();
    $factura = $resultado_factura->fetch_assoc();

    // Verificar que la factura existe
    if (!$factura) {
        throw new Exception("La factura con ID $id_factura no existe.");
    }

    // Consultar los detalles (productos) de la factura
    // NOTA: Corregido para usar los nombres correctos de columnas según schema.sql
    $stmt_detalle = $conexion->prepare("
        SELECT 
            d.cantidad,
            d.precio_unitario,
            d.impuesto,
            p.producto_codigo,
            p.producto_nombre
        FROM detalle_factura d 
        JOIN producto p ON d.id_producto = p.producto_id 
        WHERE d.id_factura = ?
        ORDER BY p.producto_nombre
    ");
    
    if (!$stmt_detalle) {
        throw new Exception("Error en la consulta de detalles: " . $conexion->error);
    }
    
    $stmt_detalle->bind_param("i", $id_factura);
    $stmt_detalle->execute();
    $detalles = $stmt_detalle->get_result();

    // Verificar que hay detalles
    if ($detalles->num_rows == 0) {
        throw new Exception("La factura no tiene productos asociados.");
    }

    // --- GENERACIÓN DEL PDF ---

    // Crear instancia del PDF
    $pdf = new PDF('P', 'mm', 'Letter');
    $pdf->AliasNbPages(); // Para numeración de páginas
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);
    
    // Información de la empresa
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, utf8_decode('EMPRESA EJEMPLO S.A.S.'), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, utf8_decode('Dirección: Calle 123 # 45-67'), 0, 1, 'L');
    $pdf->Cell(0, 5, utf8_decode('Teléfono: (57) 1 234 5678'), 0, 1, 'L');
    $pdf->Cell(0, 5, utf8_decode('Email: info@empresa.com'), 0, 1, 'L');
    $pdf->Cell(0, 5, utf8_decode('NIT: 900.123.456-7'), 0, 1, 'L');
    $pdf->Ln(10);

    // Información de la factura
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 8, 'Factura No:', 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'FAC-' . str_pad($factura['id'], 6, '0', STR_PAD_LEFT), 0, 1);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 8, 'Fecha:', 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, date("d/m/Y H:i", strtotime($factura['fecha'])), 0, 1);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 8, 'Cliente:', 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, htmlspecialchars($factura['identificacion_cliente']), 0, 1);
    $pdf->Ln(10);

    // Encabezados de la tabla de productos
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(240, 240, 240); // Color de fondo gris claro
    
    // Definir anchos de columnas
    $w_codigo = 30;
    $w_producto = 70;
    $w_cantidad = 20;
    $w_precio = 25;
    $w_impuesto = 20;
    $w_subtotal = 30;
    
    $pdf->Cell($w_codigo, 8, utf8_decode('CÓDIGO'), 1, 0, 'C', true);
    $pdf->Cell($w_producto, 8, utf8_decode('PRODUCTO'), 1, 0, 'C', true);
    $pdf->Cell($w_cantidad, 8, utf8_decode('CANT.'), 1, 0, 'C', true);
    $pdf->Cell($w_precio, 8, utf8_decode('PRECIO UNIT.'), 1, 0, 'C', true);
    $pdf->Cell($w_impuesto, 8, utf8_decode('IMPUESTO'), 1, 0, 'C', true);
    $pdf->Cell($w_subtotal, 8, utf8_decode('SUBTOTAL'), 1, 1, 'C', true);
    
    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 9);
    $subtotal_sin_impuesto = 0;
    $total_impuestos = 0;
    
    while ($detalle = $detalles->fetch_assoc()) {
        $subtotal_item = $detalle['cantidad'] * $detalle['precio_unitario'];
        $impuesto_item = $subtotal_item * ($detalle['impuesto'] / 100);
        $total_item = $subtotal_item + $impuesto_item;
        
        $subtotal_sin_impuesto += $subtotal_item;
        $total_impuestos += $impuesto_item;
        
        // Verificar si el texto es muy largo para la celda
        $nombre_producto = $detalle['producto_nombre'];
        if (strlen($nombre_producto) > 25) {
            $nombre_producto = substr($nombre_producto, 0, 22) . '...';
        }
        
        $pdf->Cell($w_codigo, 6, $detalle['producto_codigo'], 1, 0, 'C');
        $pdf->Cell($w_producto, 6, utf8_decode($nombre_producto), 1, 0, 'L');
        $pdf->Cell($w_cantidad, 6, $detalle['cantidad'], 1, 0, 'C');
        $pdf->Cell($w_precio, 6, '$' . number_format($detalle['precio_unitario'], 2), 1, 0, 'R');
        $pdf->Cell($w_impuesto, 6, '$' . number_format($impuesto_item, 2), 1, 0, 'R');
        $pdf->Cell($w_subtotal, 6, '$' . number_format($total_item, 2), 1, 1, 'R');
    }

    // Totales
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 10);
    
    // Subtotal sin impuestos
    $pdf->Cell(155, 8, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(30, 8, '$' . number_format($subtotal_sin_impuesto, 2), 1, 1, 'R');
    
    // Total impuestos
    $pdf->Cell(155, 8, 'Impuestos (19%):', 0, 0, 'R');
    $pdf->Cell(30, 8, '$' . number_format($total_impuestos, 2), 1, 1, 'R');
    
    // Línea separadora
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(2);
    
    // Total final
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(155, 10, 'TOTAL:', 0, 0, 'R');
    $pdf->Cell(30, 10, '$' . number_format($factura['total'], 2), 1, 1, 'R');
    
    // Información adicional
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(0, 5, utf8_decode('Gracias por su compra. Este documento es una representación impresa de una factura electrónica.'), 0, 1, 'C');
    $pdf->Cell(0, 5, utf8_decode('Para consultas o soporte técnico, contacte a soporte@empresa.com'), 0, 1, 'C');
    
    // Generar nombre del archivo
    $nombre_archivo = 'factura_' . str_pad($id_factura, 6, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.pdf';
    
    // Salida del PDF (D: para forzar descarga)
    $pdf->Output('D', $nombre_archivo);

} catch (Exception $e) {
    // En caso de error, mostrar mensaje amigable
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h2 style='color: #d32f2f;'>Error al generar el PDF</h2>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='dashboard.php' style='color: #1976d2; text-decoration: none;'>← Volver al Dashboard</a></p>";
    echo "</div>";
}
?>
