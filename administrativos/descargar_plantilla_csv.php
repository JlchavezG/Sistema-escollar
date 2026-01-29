<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

// Generar CSV con formato profesional
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="plantilla_alumnos_' . date('Y-m-d') . '.csv"');

// Salida UTF-8 con BOM para compatibilidad Excel en Windows/Mac
$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados
$headers = ['NOMBRE', 'APELLIDO_PATERNO', 'APELLIDO_MATERNO', 'FECHA_NACIMIENTO (AAAA-MM-DD)', 'GRADO', 'GRUPO', 'TUTOR_NOMBRE', 'TUTOR_TELEFONO'];
fputcsv($output, $headers);

// Datos de ejemplo
$exampleData = [
    ['Juan', 'Pérez', 'García', '2010-05-15', '1° Primaria', 'A', 'María Pérez', '555-1234'],
    ['Ana', 'López', 'Rodríguez', '2009-08-22', '2° Primaria', 'B', 'Carlos López', '555-5678'],
    ['Carlos', 'Hernández', 'Martínez', '2008-03-10', '3° Primaria', 'C', 'Laura Hernández', '555-9012']
];

// Escribir datos de ejemplo
foreach ($exampleData as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>