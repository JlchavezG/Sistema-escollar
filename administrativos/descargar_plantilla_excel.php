<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

// Verificar si PhpSpreadsheet está instalado
if (!file_exists('../vendor/autoload.php')) {
    die('<div style="padding: 50px; text-align: center; font-family: Arial, sans-serif;">
        <h2 style="color: #e74c3c;">❌ Error: PhpSpreadsheet no instalado</h2>
        <p style="font-size: 18px; margin: 20px 0;">Para usar esta función, instala PhpSpreadsheet con Composer:</p>
        <code style="background: #f8f9fa; padding: 15px; border-radius: 5px; display: inline-block; margin: 20px 0; font-size: 16px;">
            composer require phpoffice/phpspreadsheet
        </code>
        <p style="margin-top: 20px;">
            <a href="gestion_alumnos.php" style="display: inline-block; background: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                ← Volver a Gestión de Alumnos
            </a>
        </p>
    </div>');
}

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Establecer título de la hoja
$sheet->setTitle('Plantilla Alumnos');

// Encabezados con estilo profesional
$headers = [
    'A1' => 'NOMBRE',
    'B1' => 'APELLIDO_PATERNO',
    'C1' => 'APELLIDO_MATERNO',
    'D1' => 'FECHA_NACIMIENTO',
    'E1' => 'GRADO',
    'F1' => 'GRUPO',
    'G1' => 'TUTOR_NOMBRE',
    'H1' => 'TUTOR_TELEFONO'
];

// Aplicar estilos a los encabezados
$styleHeader = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12,
        'name' => 'Arial'
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2C3E50'], // Azul marino profesional
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
];

// Escribir encabezados y aplicar estilo
foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
    $sheet->getStyle($cell)->applyFromArray($styleHeader);
}

// Datos de ejemplo con validación visual
$exampleData = [
    ['Juan', 'Pérez', 'García', '2010-05-15', '1° Primaria', 'A', 'María Pérez', '555-1234'],
    ['Ana', 'López', 'Rodríguez', '2009-08-22', '2° Primaria', 'B', 'Carlos López', '555-5678'],
    ['Carlos', 'Hernández', 'Martínez', '2008-03-10', '3° Primaria', 'C', 'Laura Hernández', '555-9012']
];

// Aplicar estilo a las celdas de datos
$styleData = [
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'D3D3D3'],
        ],
    ],
    'font' => [
        'name' => 'Arial',
        'size' => 11
    ]
];

// Escribir datos de ejemplo
$row = 2;
foreach ($exampleData as $data) {
    $col = 'A';
    foreach ($data as $value) {
        $sheet->setCellValue($col . $row, $value);
        $sheet->getStyle($col . $row)->applyFromArray($styleData);
        $col++;
    }
    $row++;
}

// Ajustar ancho de columnas
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(10);
$sheet->getColumnDimension('G')->setWidth(25);
$sheet->getColumnDimension('H')->setWidth(15);

// Agregar instrucciones en una hoja separada
$instructionsSheet = $spreadsheet->createSheet();
$instructionsSheet->setTitle('INSTRUCCIONES');

$instructions = [
    ['PLANTILLA PARA IMPORTACIÓN MASIVA DE ALUMNOS', '', '', '', '', '', '', ''],
    ['', '', '', '', '', '', '', ''],
    ['INSTRUCCIONES:', '', '', '', '', '', '', ''],
    ['1. Complete los datos en la hoja "Plantilla Alumnos"', '', '', '', '', '', '', ''],
    ['2. Formato de fecha: AAAA-MM-DD (ej: 2010-05-15)', '', '', '', '', '', '', ''],
    ['3. Grados válidos: ' . implode(', ', getGrades()), '', '', '', '', '', '', ''],
    ['4. Grupos válidos: ' . implode(', ', getGroups()), '', '', '', '', '', '', ''],
    ['5. Todos los campos son obligatorios excepto APELLIDO_MATERNO', '', '', '', '', '', '', ''],
    ['6. No elimine ni modifique los encabezados', '', '', '', '', '', '', ''],
    ['7. Guarde el archivo en formato Excel (.xlsx)', '', '', '', '', '', '', ''],
    ['', '', '', '', '', '', '', ''],
    ['EJEMPLO DE DATOS VÁLIDOS:', '', '', '', '', '', '', ''],
    ['Nombre: Juan', '', '', '', '', '', '', ''],
    ['Apellido Paterno: Pérez', '', '', '', '', '', '', ''],
    ['Apellido Materno: García', '', '', '', '', '', '', ''],
    ['Fecha Nacimiento: 2010-05-15', '', '', '', '', '', '', ''],
    ['Grado: 1° Primaria', '', '', '', '', '', '', ''],
    ['Grupo: A', '', '', '', '', '', '', ''],
    ['Tutor Nombre: María Pérez', '', '', '', '', '', '', ''],
    ['Tutor Teléfono: 555-1234', '', '', '', '', '', '', '']
];

$styleInstructionsHeader = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => '2C3E50'],
        'size' => 14,
        'name' => 'Arial'
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

$styleInstructions = [
    'font' => [
        'name' => 'Arial',
        'size' => 11
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
];

// Escribir instrucciones
$row = 1;
foreach ($instructions as $data) {
    $col = 'A';
    foreach ($data as $value) {
        $instructionsSheet->setCellValue($col . $row, $value);
        if ($row == 1) {
            $instructionsSheet->getStyle($col . $row)->applyFromArray($styleInstructionsHeader);
            $instructionsSheet->mergeCells('A1:H1');
        } else {
            $instructionsSheet->getStyle($col . $row)->applyFromArray($styleInstructions);
        }
        $col++;
    }
    $row++;
}

// Establecer la hoja de instrucciones como activa al abrir
$spreadsheet->setActiveSheetIndex(1);

// Configurar headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="plantilla_alumnos_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>