<?php
// generar_pdf.php

// __construct(orientation[PORTRAIT, LANDSCAPE],unit[pt as point, mm as millimeter, cm as centimeter, in as inch], size[A3, A4, A5, Letter, Legal])
//AddPage(orientation[PORTRAIT, LANDSCAPE], size[A3, A4, A5, LETTER, LEGAL])
//SetFont(tipo[COURIER, HELVETICA, ARIAL, TIMES, SYMBOL, ZAPDINGBATS], estilo[normal, B, I, U], tamaño)
// Cell(ancho, alto, texto, bordes, ?, alineacion, rellenar, link)
//OutPut(destino[I, D, F, S], nombre_archivo, utf8)

// Requerimos la biblioteca FPDF y la conexión a la BD
define('FPDF_FONTPATH', __DIR__ . '/fpdf/font/');
// require('fpdf/fpdf.php');
require 'conexion.php';

// Creamos una clase que extiende de FPDF para poder crear un encabezado y pie de página
class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'Factura de Venta', 0, 1, 'C');
        $this->Ln(10);
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Verificamos que se haya pasado un ID de factura por la URL
if (isset($_GET['id_factura'])) {
    $id_factura = $_GET['id_factura'];

    // Consultamos los datos de la factura
    $stmt_factura = $conexion->prepare("SELECT * FROM facturas WHERE id = ?");
    $stmt_factura->bind_param("i", $id_factura);
    $stmt_factura->execute();
    $factura = $stmt_factura->get_result()->fetch_assoc();

    // Consultamos los detalles (productos) de la factura
    $stmt_detalle = $conexion->prepare("
        SELECT d.*, p.nombre, p.codigo 
        FROM detalle_factura d 
        JOIN productos p ON d.id_producto = p.id 
        WHERE d.id_factura = ?
    ");
    $stmt_detalle->bind_param("i", $id_factura);
    $stmt_detalle->execute();
    $detalles = $stmt_detalle->get_result();

    // Creación del PDF
    $pdf = new PDF('P','cm','Letter');
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);
    
    // Información de la factura
    $pdf->Cell(40, 10, 'Factura No:', 0, 0);
    $pdf->Cell(0, 10, $factura['id'], 0, 1);
    $pdf->Cell(40, 10, 'Fecha:', 0, 0);
    $pdf->Cell(0, 10, date("d/m/Y H:i", strtotime($factura['fecha'])), 0, 1);
    $pdf->Cell(40, 10, 'Cliente:', 0, 0);
    $pdf->Cell(0, 10, $factura['identificacion_cliente'], 0, 1);
    $pdf->Ln(10);

    // Encabezados de la tabla de productos
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 10, 'Codigo', 1, 0, 'C');
    $pdf->Cell(70, 10, 'Producto', 1, 0, 'C');
    $pdf->Cell(25, 10, 'Cantidad', 1, 0, 'C');
    $pdf->Cell(25, 10, 'Precio', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Subtotal', 1, 1, 'C');
    
    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 10);
    while ($detalle = $detalles->fetch_assoc()) {
        $subtotal_item = $detalle['cantidad'] * $detalle['precio_unitario'];
        $pdf->Cell(40, 10, $detalle['codigo'], 1, 0);
        $pdf->Cell(70, 10, mb_convert_encoding($detalle['nombre'], 'ISO-8859-1', 'UTF-8'), 1, 0); // Convertir UTF-8 a ISO-8859-1 para FPDF
        $pdf->Cell(25, 10, $detalle['cantidad'], 1, 0, 'C');
        $pdf->Cell(25, 10, '$' . number_format($detalle['precio_unitario'], 2), 1, 0, 'R');
        $pdf->Cell(30, 10, '$' . number_format($subtotal_item, 2), 1, 1, 'R');
    }

    // Total final
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(130, 10, '', 0, 0);
    $pdf->Cell(30, 10, 'Total:', 1, 0, 'R');
    $pdf->Cell(30, 10, '$' . number_format($factura['total'], 2), 1, 1, 'R');
    
    // Salida del PDF (D: para forzar descarga)
    $pdf->Output('D', 'factura_' . $id_factura . '.pdf');

} else {
    die("Error: No se proporcionó un ID de factura.");
}
?>
