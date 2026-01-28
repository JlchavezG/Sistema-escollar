<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$message = '';
$message_type = '';
$success_count = 0;
$error_count = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    $file = $_FILES['archivo_excel'];
    
    // Validar archivo
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Error al subir el archivo. Código de error: ' . $file['error'];
        $message_type = 'danger';
    } elseif ($file['size'] > 5000000) { // 5MB máximo
        $message = 'El archivo es demasiado grande (máximo 5MB)';
        $message_type = 'danger';
    } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'xlsx') {
        $message = 'Solo se permiten archivos Excel (.xlsx)';
        $message_type = 'danger';
    } else {
        // Verificar si PhpSpreadsheet está instalado
        if (!file_exists('../vendor/autoload.php')) {
            $message = 'PhpSpreadsheet no está instalado. Ejecuta: composer require phpoffice/phpspreadsheet';
            $message_type = 'danger';
        } else {
            try {
                require_once '../vendor/autoload.php';
                use PhpOffice\PhpSpreadsheet\IOFactory;
                
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                // Validar que haya al menos 2 filas (encabezados + 1 dato)
                if (count($rows) < 2) {
                    throw new Exception('El archivo está vacío o no tiene datos. Debe contener al menos una fila de datos.');
                }
                
                // Validar encabezados
                $expectedHeaders = ['NOMBRE', 'APELLIDO_PATERNO', 'APELLIDO_MATERNO', 'FECHA_NACIMIENTO', 'GRADO', 'GRUPO', 'TUTOR_NOMBRE', 'TUTOR_TELEFONO'];
                $actualHeaders = array_map('strtoupper', array_map('trim', $rows[0]));
                
                // Verificar que todos los encabezados esperados estén presentes
                $missingHeaders = array_diff($expectedHeaders, $actualHeaders);
                if (!empty($missingHeaders)) {
                    throw new Exception('El archivo no tiene el formato correcto. Faltan los siguientes encabezados: ' . implode(', ', $missingHeaders) . '. Descarga la plantilla oficial.');
                }
                
                // Procesar filas (omitir encabezados)
                $db->beginTransaction();
                $rowIndex = 2; // Empezar desde la segunda fila (después de encabezados)
                
                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    // Saltar filas completamente vacías
                    if (empty(array_filter($row, function($value) { return trim($value) !== ''; }))) {
                        continue;
                    }
                    
                    try {
                        // Validar campos obligatorios (índices según el orden de la plantilla)
                        // 0: NOMBRE, 1: APELLIDO_PATERNO, 3: FECHA_NACIMIENTO, 4: GRADO, 5: GRUPO
                        if (empty(trim($row[0] ?? '')) || 
                            empty(trim($row[1] ?? '')) || 
                            empty(trim($row[3] ?? '')) || 
                            empty(trim($row[4] ?? '')) || 
                            empty(trim($row[5] ?? ''))) {
                            throw new Exception("Faltan campos obligatorios en la fila $rowIndex");
                        }
                        
                        // Validar fecha
                        $fecha_nac = DateTime::createFromFormat('Y-m-d', trim($row[3]));
                        if (!$fecha_nac) {
                            throw new Exception("Fecha inválida en la fila $rowIndex. Formato: AAAA-MM-DD");
                        }
                        
                        // Validar grado
                        $grados_validos = getGrades();
                        if (!in_array(trim($row[4]), $grados_validos)) {
                            throw new Exception("Grado inválido en la fila $rowIndex. Valores permitidos: " . implode(', ', $grados_validos));
                        }
                        
                        // Validar grupo
                        $grupos_validos = getGroups();
                        if (!in_array(trim($row[5]), $grupos_validos)) {
                            throw new Exception("Grupo inválido en la fila $rowIndex. Valores permitidos: " . implode(', ', $grupos_validos));
                        }
                        
                        // Insertar alumno
                        $db->query("INSERT INTO alumnos (nombre, apellido_paterno, apellido_materno, fecha_nacimiento, grado, grupo, tutor_nombre, tutor_telefono, created_at) 
                                    VALUES (:nombre, :apellido_paterno, :apellido_materno, :fecha_nacimiento, :grado, :grupo, :tutor_nombre, :tutor_telefono, NOW())");
                        
                        $db->bind(':nombre', sanitizeInput(trim($row[0])));
                        $db->bind(':apellido_paterno', sanitizeInput(trim($row[1])));
                        $db->bind(':apellido_materno', sanitizeInput(trim($row[2]) ?? ''));
                        $db->bind(':fecha_nacimiento', $fecha_nac->format('Y-m-d'));
                        $db->bind(':grado', sanitizeInput(trim($row[4])));
                        $db->bind(':grupo', sanitizeInput(trim($row[5])));
                        $db->bind(':tutor_nombre', sanitizeInput(trim($row[6]) ?? ''));
                        $db->bind(':tutor_telefono', sanitizeInput(trim($row[7]) ?? ''));
                        
                        $db->execute();
                        $success_count++;
                        
                    } catch (Exception $e) {
                        $error_count++;
                        $errors[] = "Fila $rowIndex: " . $e->getMessage();
                    }
                    
                    $rowIndex++;
                }
                
                $db->endTransaction();
                
                if ($success_count > 0) {
                    $message = "¡Importación exitosa! $success_count alumnos registrados.";
                    if ($error_count > 0) {
                        $message .= " $error_count registros fallidos.";
                    }
                    $message_type = 'success';
                } else {
                    $message = 'No se registraron alumnos. Verifica el formato del archivo.';
                    $message_type = 'warning';
                }
                
            } catch (Exception $e) {
                $db->cancelTransaction();
                $message = 'Error al procesar el archivo: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Guardar resultados en sesión para mostrar en gestión_alumnos.php
$_SESSION['import_message'] = $message;
$_SESSION['import_message_type'] = $message_type;
$_SESSION['import_success_count'] = $success_count;
$_SESSION['import_error_count'] = $error_count;
$_SESSION['import_errors'] = $errors;

header('Location: gestion_alumnos.php');
exit();
?>