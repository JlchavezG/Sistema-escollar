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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_csv'])) {
    $file = $_FILES['archivo_csv'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Error al subir el archivo';
        $message_type = 'danger';
    } elseif ($file['size'] > 5000000) {
        $message = 'El archivo es demasiado grande (máximo 5MB)';
        $message_type = 'danger';
    } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
        $message = 'Solo se permiten archivos CSV (.csv)';
        $message_type = 'danger';
    } else {
        try {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) throw new Exception('No se pudo abrir el archivo CSV');
            
            $headers = fgetcsv($handle, 1000, ',');
            if (!$headers) {
                fclose($handle);
                throw new Exception('El archivo CSV está vacío o no tiene encabezados');
            }
            
            $requiredHeaders = ['NOMBRE', 'APELLIDO_PATERNO', 'FECHA_NACIMIENTO', 'GRADO', 'GRUPO'];
            $missingHeaders = [];
            
            foreach ($requiredHeaders as $header) {
                $found = false;
                foreach ($headers as $h) {
                    if (strpos(strtoupper(trim($h)), strtoupper($header)) !== false) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) $missingHeaders[] = $header;
            }
            
            if (!empty($missingHeaders)) {
                fclose($handle);
                throw new Exception('Faltan encabezados: ' . implode(', ', $missingHeaders));
            }
            
            $db->beginTransaction();
            $rowIndex = 2;
            
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (empty(array_filter($row, function($value) { return trim($value) !== ''; }))) {
                    $rowIndex++;
                    continue;
                }
                
                try {
                    $nombre = trim($row[0] ?? '');
                    $apellido_paterno = trim($row[1] ?? '');
                    $apellido_materno = trim($row[2] ?? '');
                    $fecha_nacimiento = trim($row[3] ?? '');
                    $grado = trim($row[4] ?? '');
                    $grupo = trim($row[5] ?? '');
                    $tutor_nombre = trim($row[6] ?? '');
                    $tutor_telefono = trim($row[7] ?? '');
                    
                    if (empty($nombre) || empty($apellido_paterno) || empty($fecha_nacimiento) || empty($grado) || empty($grupo)) {
                        throw new Exception("Faltan campos obligatorios en la fila $rowIndex");
                    }
                    
                    $fecha_nac = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
                    if (!$fecha_nac) throw new Exception("Fecha inválida en la fila $rowIndex. Formato: AAAA-MM-DD");
                    
                    $grados_validos = getGrades();
                    if (!in_array($grado, $grados_validos)) throw new Exception("Grado inválido en la fila $rowIndex");
                    
                    $grupos_validos = getGroups();
                    if (!in_array($grupo, $grupos_validos)) throw new Exception("Grupo inválido en la fila $rowIndex");
                    
                    $db->query("INSERT INTO alumnos (nombre, apellido_paterno, apellido_materno, fecha_nacimiento, grado, grupo, tutor_nombre, tutor_telefono, created_at) 
                                VALUES (:nombre, :apellido_paterno, :apellido_materno, :fecha_nacimiento, :grado, :grupo, :tutor_nombre, :tutor_telefono, NOW())");
                    
                    $db->bind(':nombre', sanitizeInput($nombre));
                    $db->bind(':apellido_paterno', sanitizeInput($apellido_paterno));
                    $db->bind(':apellido_materno', sanitizeInput($apellido_materno));
                    $db->bind(':fecha_nacimiento', $fecha_nac->format('Y-m-d'));
                    $db->bind(':grado', sanitizeInput($grado));
                    $db->bind(':grupo', sanitizeInput($grupo));
                    $db->bind(':tutor_nombre', sanitizeInput($tutor_nombre));
                    $db->bind(':tutor_telefono', sanitizeInput($tutor_telefono));
                    
                    $db->execute();
                    $success_count++;
                    
                } catch (Exception $e) {
                    $error_count++;
                    $errors[] = "Fila $rowIndex: " . $e->getMessage();
                }
                
                $rowIndex++;
            }
            
            fclose($handle);
            $db->endTransaction();
            
            if ($success_count > 0) {
                $message = "¡Importación exitosa! $success_count alumnos registrados.";
                if ($error_count > 0) $message .= " $error_count registros fallidos.";
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

$_SESSION['import_message'] = $message;
$_SESSION['import_message_type'] = $message_type;
$_SESSION['import_success_count'] = $success_count;
$_SESSION['import_error_count'] = $error_count;
$_SESSION['import_errors'] = $errors;

header('Location: gestion_alumnos.php');
exit();
?>