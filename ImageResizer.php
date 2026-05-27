<?php
/**
 * ImageResizer Class
 * A robust PHP class to load, resize, crop, and convert images using the GD extension.
 */

class ImageResizer {
    private $image;
    private $imagePath;
    private $width;
    private $height;
    private $mimeType;
    private $imageType;

    // Supported MIME Types
    private static $supportedMimeTypes = [
        'image/jpeg' => IMAGETYPE_JPEG,
        'image/jpg'  => IMAGETYPE_JPEG,
        'image/png'  => IMAGETYPE_PNG,
        'image/gif'  => IMAGETYPE_GIF,
        'image/webp' => IMAGETYPE_WEBP
    ];

    /**
     * Constructor
     * 
     * @param string $imagePath Path to the source image file
     * @throws Exception If GD extension is missing or file is invalid
     */
    public function __construct($imagePath = null) {
        if (!extension_loaded('gd')) {
            throw new Exception("La extensión GD de PHP no está instalada o habilitada.");
        }

        if ($imagePath !== null) {
            $this->load($imagePath);
        }
    }

    /**
     * Load an image file
     * 
     * @param string $imagePath Path to the source image file
     * @throws Exception If file does not exist, is not readable, or is not supported
     */
    public function load($imagePath) {
        if (!file_exists($imagePath)) {
            throw new Exception("El archivo no existe: " . htmlspecialchars($imagePath));
        }

        if (!is_readable($imagePath)) {
            throw new Exception("El archivo no es accesible para lectura: " . htmlspecialchars($imagePath));
        }

        $imageInfo = @getimagesize($imagePath);
        if ($imageInfo === false) {
            throw new Exception("El archivo no es una imagen válida o está dañado.");
        }

        $this->imagePath = $imagePath;
        $this->width = $imageInfo[0];
        $this->height = $imageInfo[1];
        $this->mimeType = $imageInfo['mime'];
        $this->imageType = $imageInfo[2];

        // Free up memory if an image was already loaded
        $this->destroy();

        // Create image resource based on type
        switch ($this->imageType) {
            Case IMAGETYPE_JPEG:
                $this->image = @imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $this->image = @imagecreatefrompng($imagePath);
                // Preserve transparency
                if ($this->image) {
                    imagealphablending($this->image, false);
                    imagesavealpha($this->image, true);
                }
                break;
            case IMAGETYPE_GIF:
                $this->image = @imagecreatefromgif($imagePath);
                if ($this->image) {
                    imagealphablending($this->image, false);
                    imagesavealpha($this->image, true);
                }
                break;
            case IMAGETYPE_WEBP:
                if (!function_exists('imagecreatefromwebp')) {
                    throw new Exception("Tu versión de PHP GD no soporta WebP.");
                }
                $this->image = @imagecreatefromwebp($imagePath);
                if ($this->image) {
                    imagealphablending($this->image, false);
                    imagesavealpha($this->image, true);
                }
                break;
            default:
                throw new Exception("Formato de imagen no soportado: " . $this->mimeType);
        }

        if (!$this->image) {
            throw new Exception("Fallo al crear el recurso de imagen desde el archivo.");
        }
    }

    /**
     * Get original width
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * Get original height
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * Get MIME Type
     */
    public function getMimeType() {
        return $this->mimeType;
    }

    /**
     * Get list of supported formats
     */
    public static function getSupportedFormats() {
        $formats = ['jpeg', 'png', 'gif'];
        if (function_exists('imagewebp')) {
            $formats[] = 'webp';
        }
        return $formats;
    }

    /**
     * Resize the loaded image
     * 
     * @param int $newWidth Target width (0 to auto-calculate based on height)
     * @param int $newHeight Target height (0 to auto-calculate based on width)
     * @param bool $maintainAspectRatio Whether to maintain original aspect ratio
     * @param bool $crop If true, crops the image from center to match exact dimensions
     * @throws Exception if dimensions are invalid or image is not loaded
     */
    public function resize($newWidth, $newHeight, $maintainAspectRatio = true, $crop = false) {
        if (!$this->image) {
            throw new Exception("No hay ninguna imagen cargada.");
        }

        $newWidth = (int)$newWidth;
        $newHeight = (int)$newHeight;

        if ($newWidth <= 0 && $newHeight <= 0) {
            throw new Exception("Las dimensiones solicitadas no son válidas.");
        }

        $srcWidth = $this->width;
        $srcHeight = $this->height;

        $srcX = 0;
        $srcY = 0;

        // Calculate dimensions
        if ($crop) {
            // Target exact dimensions, cropping excess from center
            if ($newWidth <= 0 || $newHeight <= 0) {
                throw new Exception("Para recortar exacto, ambas dimensiones deben ser mayores a 0.");
            }

            $ratioSrc = $srcWidth / $srcHeight;
            $ratioDst = $newWidth / $newHeight;

            if ($ratioSrc > $ratioDst) {
                // Original is wider than target ratio: crop sides
                $tempWidth = (int)($srcHeight * $ratioDst);
                $srcX = (int)(($srcWidth - $tempWidth) / 2);
                $srcWidth = $tempWidth;
            } else {
                // Original is taller than target ratio: crop top/bottom
                $tempHeight = (int)($srcWidth / $ratioDst);
                $srcY = (int)(($srcHeight - $tempHeight) / 2);
                $srcHeight = $tempHeight;
            }
        } elseif ($maintainAspectRatio) {
            // Keep proportion, fit within boundaries
            if ($newWidth > 0 && $newHeight > 0) {
                $ratioSrc = $srcWidth / $srcHeight;
                if ($newWidth / $newHeight > $ratioSrc) {
                    $newWidth = (int)($newHeight * $ratioSrc);
                } else {
                    $newHeight = (int)($newWidth / $ratioSrc);
                }
            } elseif ($newWidth > 0) {
                // Auto-calculate height based on width
                $newHeight = (int)($newWidth * ($srcHeight / $srcWidth));
            } else {
                // Auto-calculate width based on height
                $newWidth = (int)($newHeight * ($srcWidth / $srcHeight));
            }
        } else {
            // Stretched to exact dimensions
            if ($newWidth <= 0) $newWidth = $srcWidth;
            if ($newHeight <= 0) $newHeight = $srcHeight;
        }

        // Create new image container
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        if (!$resizedImage) {
            throw new Exception("No se pudo inicializar el lienzo para la nueva imagen.");
        }

        // Configure alpha channel and transparency for new canvas
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);

        // Resize using imagecopyresampled (high quality interpolation)
        $success = imagecopyresampled(
            $resizedImage, 
            $this->image, 
            0, 0, // dest x, y
            $srcX, $srcY, // src x, y
            $newWidth, $newHeight, // dest w, h
            $srcWidth, $srcHeight // src w, h
        );

        if (!$success) {
            imagedestroy($resizedImage);
            throw new Exception("Error al procesar la redimensión de la imagen.");
        }

        // Replace original memory resource with new resized resource
        imagedestroy($this->image);
        $this->image = $resizedImage;
        $this->width = $newWidth;
        $this->height = $newHeight;

        return $this;
    }

    /**
     * Save the resized image
     * 
     * @param string $savePath Path where to save the output file
     * @param string $outputFormat Format to convert to (jpg/jpeg, png, gif, webp). If null, keeps original
     * @param int $quality Quality factor for JPEG/WebP (0-100), compression for PNG (0-9)
     * @throws Exception if write operation fails
     */
    public function save($savePath, $outputFormat = null, $quality = 90) {
        if (!$this->image) {
            throw new Exception("No hay ninguna imagen cargada para guardar.");
        }

        $quality = (int)$quality;
        if ($quality < 0) $quality = 0;
        if ($quality > 100) $quality = 100;

        // Determine output type
        $saveType = $this->imageType;
        if ($outputFormat !== null) {
            $format = strtolower(trim($outputFormat));
            if ($format === 'jpg' || $format === 'jpeg') {
                $saveType = IMAGETYPE_JPEG;
            } elseif ($format === 'png') {
                $saveType = IMAGETYPE_PNG;
            } elseif ($format === 'gif') {
                $saveType = IMAGETYPE_GIF;
            } elseif ($format === 'webp') {
                $saveType = IMAGETYPE_WEBP;
            } else {
                throw new Exception("Formato de salida solicitado no soportado: " . $outputFormat);
            }
        }

        // Create target directory if it doesn't exist
        $dir = dirname($savePath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception("No se pudo crear el directorio de destino: " . $dir);
            }
        }

        $success = false;

        switch ($saveType) {
            case IMAGETYPE_JPEG:
                // JPEGs don't support alpha transparency, so we optionally fill background with white
                // to prevent black backgrounds on transparent PNGs/GIFs
                $tempCanvas = imagecreatetruecolor($this->width, $this->height);
                $white = imagecolorallocate($tempCanvas, 255, 255, 255);
                imagefill($tempCanvas, 0, 0, $white);
                imagecopy($tempCanvas, $this->image, 0, 0, 0, 0, $this->width, $this->height);
                $success = imagejpeg($tempCanvas, $savePath, $quality);
                imagedestroy($tempCanvas);
                break;

            case IMAGETYPE_PNG:
                // PNG quality parameter is a compression level (0-9), reverse of standard quality
                // quality input is 0-100: map 0-100 to 9-0 compression
                $pngCompression = (int)round((100 - $quality) / 11.1);
                if ($pngCompression < 0) $pngCompression = 0;
                if ($pngCompression > 9) $pngCompression = 9;
                
                $success = imagepng($this->image, $savePath, $pngCompression);
                break;

            case IMAGETYPE_GIF:
                $success = imagegif($this->image, $savePath);
                break;

            case IMAGETYPE_WEBP:
                if (!function_exists('imagewebp')) {
                    throw new Exception("La extensión GD instalada no soporta WebP.");
                }
                $success = imagewebp($this->image, $savePath, $quality);
                break;

            default:
                throw new Exception("Tipo de guardado desconocido.");
        }

        if (!$success) {
            throw new Exception("No se pudo escribir el archivo en el disco: " . htmlspecialchars($savePath));
        }

        return true;
    }

    /**
     * Destructor and memory cleanup
     */
    public function destroy() {
        if ($this->image && is_resource($this->image)) {
            imagedestroy($this->image);
        }
        $this->image = null;
    }

    public function __destruct() {
        $this->destroy();
    }
}
