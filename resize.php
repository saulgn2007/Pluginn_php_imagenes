<?php
/**
 * resize.php
 * Endpoint AJAX para procesar la subida y redimensión de imágenes.
 */

header('Content-Type: application/json; charset=utf-8');

// Configuración de visualización de errores para desarrollo controlado
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'ImageResizer.php';

$uploadDir = __DIR__ . '/uploads/';

// Función para limpiar archivos antiguos del directorio temporal (más de 15 minutos)
function cleanOldFiles($dir) {
    if (!is_dir($dir)) return;
    $files = glob($dir . '*');
    $now = time();
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 900) { // 15 minutos en segundos
                @unlink($file);
            }
        }
    }
}

try {
    // 1. Limpieza de archivos antiguos de forma automática
    cleanOldFiles($uploadDir);

    // 2. Validar que la petición sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método de petición no permitido. Debe ser POST.");
    }

    // 3. Validar si existe el archivo subido
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = isset($_FILES['image']['error']) ? $_FILES['image']['error'] : UPLOAD_ERR_NO_FILE;
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception("El archivo excede el tamaño máximo permitido por el servidor.");
            case UPLOAD_ERR_PARTIAL:
                throw new Exception("El archivo se subió solo parcialmente.");
            case UPLOAD_ERR_NO_FILE:
                throw new Exception("No se ha seleccionado ninguna imagen.");
            default:
                throw new Exception("Fallo en la subida de la imagen (Código de error: $errorCode).");
        }
    }

    $uploadedFile = $_FILES['image'];

    // Validar tamaño máximo en PHP (15MB por ejemplo)
    $maxSize = 15 * 1024 * 1024; // 15MB
    if ($uploadedFile['size'] > $maxSize) {
        throw new Exception("El archivo es demasiado grande. El límite es de 15MB.");
    }

    // Asegurar que el directorio de subidas existe
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception("No se pudo crear el directorio temporal 'uploads/'.");
        }
    }

    // Generar nombres de archivo seguros
    $originalName = pathinfo($uploadedFile['name'], PATHINFO_FILENAME);
    $originalName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalName); // Sanear
    if (empty($originalName)) {
        $originalName = 'image_' . time();
    }

    // Mover archivo temporal subido
    $tempInputPath = $uploadDir . 'tmp_' . uniqid() . '_' . $uploadedFile['name'];
    if (!move_uploaded_file($uploadedFile['tmp_name'], $tempInputPath)) {
        throw new Exception("No se pudo guardar el archivo temporal subido.");
    }

    // 4. Leer y sanitizar parámetros
    $width = isset($_POST['width']) ? (int)$_POST['width'] : 0;
    $height = isset($_POST['height']) ? (int)$_POST['height'] : 0;
    $quality = isset($_POST['quality']) ? (int)$_POST['quality'] : 90;
    $aspectRatio = isset($_POST['aspect_ratio']) && $_POST['aspect_ratio'] === 'true';
    $crop = isset($_POST['crop']) && $_POST['crop'] === 'true';
    $format = isset($_POST['format']) ? strtolower(trim($_POST['format'])) : 'original';

    // 5. Procesar la imagen con ImageResizer
    $resizer = new ImageResizer($tempInputPath);
    
    $origWidth = $resizer->getWidth();
    $origHeight = $resizer->getHeight();
    $origSize = filesize($tempInputPath);

    // Ajustar dimensiones si una de ellas es auto (0) y no se realiza crop
    if ($width <= 0 && $height <= 0) {
        $width = $origWidth;
        $height = $origHeight;
    }

    // Ejecutar redimensión
    $resizer->resize($width, $height, $aspectRatio, $crop);

    // Determinar extensión del archivo de salida
    $outFormat = $format;
    if ($format === 'original') {
        $mime = $resizer->getMimeType();
        $outFormat = str_replace('image/', '', $mime);
        if ($outFormat === 'jpeg') $outFormat = 'jpg';
    }

    // Generar nombre definitivo para el archivo de salida
    $resizedFilename = $originalName . '_' . $resizer->getWidth() . 'x' . $resizer->getHeight() . '.' . $outFormat;
    $uniqueResizedFilename = uniqid() . '_' . $resizedFilename;
    $outputPath = $uploadDir . $uniqueResizedFilename;

    // Guardar
    $resizer->save($outputPath, ($format === 'original' ? null : $format), $quality);

    // Calcular tamaño final
    $resizedSize = filesize($outputPath);

    // Limpiar recurso de resizer
    $resizer->destroy();
    
    // Eliminar la imagen temporal de entrada
    @unlink($tempInputPath);

    // Calcular ahorro
    $savingsBytes = $origSize - $resizedSize;
    $savingsPercent = $origSize > 0 ? round(($savingsBytes / $origSize) * 100) : 0;
    if ($savingsPercent < 0) {
        $savingsPercent = 0; // Se incrementó el tamaño del archivo
    }

    // Construir la URL relativa del archivo descargable
    $downloadUrl = 'uploads/' . $uniqueResizedFilename;

    // Retornar éxito
    echo json_encode([
        'success' => true,
        'message' => 'Imagen redimensionada con éxito.',
        'data' => [
            'download_url' => $downloadUrl,
            'filename' => $resizedFilename,
            'original' => [
                'width' => $origWidth,
                'height' => $origHeight,
                'size' => $origSize,
                'size_formatted' => formatBytes($origSize)
            ],
            'resized' => [
                'width' => $resizer->getWidth(),
                'height' => $resizer->getHeight(),
                'size' => $resizedSize,
                'size_formatted' => formatBytes($resizedSize)
            ],
            'savings' => [
                'bytes' => $savingsBytes,
                'percent' => $savingsPercent,
                'formatted' => formatBytes(abs($savingsBytes))
            ]
        ]
    ]);

} catch (Exception $e) {
    // Si ocurre un error, eliminar el archivo temporal de entrada
    if (isset($tempInputPath) && file_exists($tempInputPath)) {
        @unlink($tempInputPath);
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Función auxiliar para dar formato legible a los tamaños en bytes
 */
function formatBytes($bytes, $decimals = 2) {
    if ($bytes <= 0) return '0 Bytes';
    $keys = array('Bytes', 'KB', 'MB', 'GB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $keys[$factor];
}
