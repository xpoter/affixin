<?php

// CREATE FILE: app/Services/WatermarkService.php

namespace App\Services;

class WatermarkService
{
    /**
     * Add watermark to an image using native PHP GD
     * 
     * @param string $imagePath - Path to the original image (relative to public)
     * @param string $watermarkText - Text to use as watermark
     * @param string $watermarkLogoPath - Path to watermark logo (optional)
     * @param string $position - Position: 'bottom-right', 'bottom-left', 'top-right', 'top-left'
     * @return string - Path to watermarked image
     */
    public function addWatermark($imagePath, $watermarkText = null, $watermarkLogoPath = null, $position = 'bottom-right')
    {
        $fullPath = public_path($imagePath);
        
        if (!file_exists($fullPath)) {
            throw new \Exception("Image not found: {$fullPath}");
        }
        
        // Get image type
        $imageInfo = getimagesize($fullPath);
        $imageType = $imageInfo[2];
        
        // Load image based on type
        $image = $this->loadImage($fullPath, $imageType);
        
        if (!$image) {
            throw new \Exception("Failed to load image");
        }
        
        // Get image dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Add text watermark
        if ($watermarkText) {
            $image = $this->addTextWatermark($image, $watermarkText, $position, $width, $height);
        }
        
        // Add logo watermark
        if ($watermarkLogoPath && file_exists(public_path($watermarkLogoPath))) {
            $image = $this->addLogoWatermark($image, $watermarkLogoPath, $position, $width, $height);
        }
        
        // Save watermarked image
        $watermarkedPath = $this->getWatermarkedPath($imagePath);
        $this->saveImage($image, public_path($watermarkedPath), $imageType);
        
        // Free memory
        imagedestroy($image);
        
        return $watermarkedPath;
    }
    
    /**
     * Load image based on type
     */
    private function loadImage($path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    /**
     * Save image based on type
     */
    private function saveImage($image, $path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $path, 90);
                break;
            case IMAGETYPE_PNG:
                // Enable alpha blending for transparency
                imagesavealpha($image, true);
                imagepng($image, $path, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($image, $path);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($image, $path, 90);
                break;
        }
    }
    
    /**
     * Add text watermark to image
     */
    private function addTextWatermark($image, $text, $position, $width, $height)
    {
        // Calculate font size (3% of smallest dimension)
        $fontSize = max(12, min($width, $height) * 0.03);
        
        // Use built-in GD font (1-5) or TTF font if available
        $fontFile = public_path('fonts/arial.ttf');
        $useFont = file_exists($fontFile);
        
        // Calculate text box dimensions
        if ($useFont) {
            $textBox = imagettfbbox($fontSize, 0, $fontFile, $text);
            $textWidth = abs($textBox[4] - $textBox[0]);
            $textHeight = abs($textBox[5] - $textBox[1]);
        } else {
            // Fallback to built-in font
            $textWidth = strlen($text) * ($fontSize * 0.6);
            $textHeight = $fontSize;
        }
        
        // Get position coordinates
        $coords = $this->getCoordinates($position, $width, $height, $textWidth, $textHeight);
        
        // Create semi-transparent background
        $padding = 10;
        $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 50); // Semi-transparent black
        imagefilledrectangle(
            $image,
            $coords['x'] - $padding,
            $coords['y'] - $textHeight - $padding,
            $coords['x'] + $textWidth + $padding,
            $coords['y'] + $padding,
            $bgColor
        );
        
        // Add text
        $textColor = imagecolorallocate($image, 255, 255, 255); // White
        
        if ($useFont) {
            imagettftext($image, $fontSize, 0, $coords['x'], $coords['y'], $textColor, $fontFile, $text);
        } else {
            // Fallback to built-in font
            imagestring($image, 5, $coords['x'], $coords['y'] - $textHeight, $text, $textColor);
        }
        
        return $image;
    }
    
    /**
     * Add logo watermark to image
     */
    private function addLogoWatermark($image, $logoPath, $position, $width, $height)
    {
        // Load logo
        $logoInfo = getimagesize(public_path($logoPath));
        $logo = $this->loadImage(public_path($logoPath), $logoInfo[2]);
        
        if (!$logo) {
            return $image;
        }
        
        // Get logo dimensions
        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        
        // Resize logo to 15% of image width
        $newLogoWidth = $width * 0.15;
        $newLogoHeight = ($logoHeight / $logoWidth) * $newLogoWidth;
        
        // Create resized logo
        $resizedLogo = imagecreatetruecolor($newLogoWidth, $newLogoHeight);
        
        // Preserve transparency for PNG
        imagealphablending($resizedLogo, false);
        imagesavealpha($resizedLogo, true);
        $transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
        imagefill($resizedLogo, 0, 0, $transparent);
        imagealphablending($resizedLogo, true);
        
        // Resize
        imagecopyresampled(
            $resizedLogo, $logo,
            0, 0, 0, 0,
            $newLogoWidth, $newLogoHeight,
            $logoWidth, $logoHeight
        );
        
        // Get position
        $coords = $this->getCoordinates($position, $width, $height, $newLogoWidth, $newLogoHeight);
        
        // Copy logo onto main image with transparency
        imagecopy($image, $resizedLogo, $coords['x'], $coords['y'], 0, 0, $newLogoWidth, $newLogoHeight);
        
        // Free memory
        imagedestroy($logo);
        imagedestroy($resizedLogo);
        
        return $image;
    }
    
    /**
     * Get coordinates based on position
     */
    private function getCoordinates($position, $width, $height, $elementWidth, $elementHeight)
    {
        $padding = 20;
        
        switch ($position) {
            case 'bottom-right':
                return [
                    'x' => $width - $elementWidth - $padding,
                    'y' => $height - $elementHeight - $padding
                ];
            case 'bottom-left':
                return [
                    'x' => $padding,
                    'y' => $height - $elementHeight - $padding
                ];
            case 'top-right':
                return [
                    'x' => $width - $elementWidth - $padding,
                    'y' => $padding
                ];
            case 'top-left':
                return [
                    'x' => $padding,
                    'y' => $padding
                ];
            case 'center':
                return [
                    'x' => ($width - $elementWidth) / 2,
                    'y' => ($height - $elementHeight) / 2
                ];
            default:
                return [
                    'x' => $width - $elementWidth - $padding,
                    'y' => $height - $elementHeight - $padding
                ];
        }
    }
    
    /**
     * Generate watermarked image path
     */
    private function getWatermarkedPath($originalPath)
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        
        return "{$directory}/{$filename}_watermarked.{$extension}";
    }
    
    /**
     * Get watermarked image (create if doesn't exist)
     */
    public function getOrCreateWatermarked($imagePath, $watermarkText = null, $watermarkLogoPath = null)
    {
        $watermarkedPath = $this->getWatermarkedPath($imagePath);
        
        if (!file_exists(public_path($watermarkedPath))) {
            return $this->addWatermark($imagePath, $watermarkText, $watermarkLogoPath);
        }
        
        return $watermarkedPath;
    }
}



