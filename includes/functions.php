<?php
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($date) {
    return date('d/m/Y H:i:s', strtotime($date));
}

function getGrades() {
    return [
        // Primaria
        '1° Primaria', 
        '2° Primaria', 
        '3° Primaria', 
        '4° Primaria', 
        '5° Primaria', 
        '6° Primaria',
        // Secundaria
        '1° Secundaria', 
        '2° Secundaria', 
        '3° Secundaria',
        // Bachillerato
        '1° Bachillerato', 
        '2° Bachillerato', 
        '3° Bachillerato',
        '4° Bachillerato', 
        '5° Bachillerato', 
        '6° Bachillerato'
    ];
}

function getGroups() {
    return ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
}

function getMonths() {
    return ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
}

function generateRandomCode($length = 8) {
    return strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length));
}

function uploadFile($file, $folder = 'uploads/') {
    $target_dir = $folder;
    $target_file = $target_dir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    $check = getimagesize($file["tmp_name"]);
    if($check !== false) {
        $uploadOk = 1;
    } else {
        return false;
    }
    
    if ($file["size"] > 5000000) {
        return false;
    }
    
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return false;
    }
    
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $new_filename;
    } else {
        return false;
    }
}

function sendEmail($to, $subject, $message, $headers = '') {
    return mail($to, $subject, $message, $headers);
}
?>