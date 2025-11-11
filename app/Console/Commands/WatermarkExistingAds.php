<?php

// CREATE FILE: app/Console/Commands/WatermarkExistingAds.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ads;
use App\Enums\AdsType;

class WatermarkExistingAds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ads:watermark 
                            {--force : Force re-watermark already watermarked images}
                            {--dry-run : Preview what would be watermarked without actually doing it}
                            {--id=* : Only watermark specific ad IDs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add watermark to all existing image ads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎨 Watermark Existing Image Ads');
        $this->newLine();

        // Get options
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $specificIds = $this->option('id');

        // Build query
        $query = Ads::where('type', AdsType::Image);

        // Filter by specific IDs if provided
        if (!empty($specificIds)) {
            $query->whereIn('id', $specificIds);
        }

        // Get all image ads
        $imageAds = $query->get();

        if ($imageAds->isEmpty()) {
            $this->error('❌ No image ads found!');
            return 0;
        }

        $this->info("Found {$imageAds->count()} image ads");
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->newLine();

        // Confirm before proceeding
        if (!$dryRun && !$this->confirm('Do you want to proceed with watermarking?', true)) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->newLine();

        // Progress bar
        $bar = $this->output->createProgressBar($imageAds->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->setMessage('Starting...');

        $success = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($imageAds as $ad) {
            $bar->setMessage("Processing Ad ID: {$ad->id}");

            try {
                // Check if already watermarked
                if (!$force && $this->isAlreadyWatermarked($ad->value)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Check if file exists
                $possiblePaths = [
                    public_path($ad->value),
                    storage_path('app/public/' . $ad->value),
                    storage_path('app/' . $ad->value),
                ];
                
                $fullPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $fullPath = $path;
                        break;
                    }
                }
                
                if (!$fullPath) {
                    $failed++;
                    $errors[] = "Ad ID {$ad->id}: File not found in any location - {$ad->value}";
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    // Add watermark
                    $watermarkedPath = $this->addWatermark($ad->value);

                    if ($watermarkedPath && $watermarkedPath !== $ad->value) {
                        // Backup original path
                        $ad->original_value = $ad->value;
                        $ad->value = $watermarkedPath;
                        $ad->save();

                        $success++;
                    } else {
                        $failed++;
                        $errors[] = "Ad ID {$ad->id}: Watermark function returned null/same path";
                    }
                } else {
                    // Dry run - just count as success
                    $success++;
                }

            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Ad ID {$ad->id}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->setMessage('Complete!');
        $bar->finish();

        $this->newLine(2);

        // Summary
        $this->info('✅ Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $success],
                ['Skipped (Already watermarked)', $skipped],
                ['Failed', $failed],
            ]
        );

        // Show errors if any
        if (!empty($errors)) {
            $this->newLine();
            $this->error('❌ Errors:');
            foreach ($errors as $error) {
                $this->line("  • {$error}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔍 This was a DRY RUN - no actual changes were made');
            $this->info('Run without --dry-run to actually watermark the images');
        }

        $this->newLine();
        $this->info('🎉 Done!');

        return 0;
    }

    /**
     * Check if image is already watermarked
     */
    private function isAlreadyWatermarked($imagePath)
    {
        // Check if filename contains '_wm' (watermarked)
        return str_contains($imagePath, '_wm.');
    }

    /**
     * Add watermark to image (same logic as controller)
     */
    private function addWatermark($imagePath)
    {
        try {
            // Handle path - prepend 'assets/' if not already there
            if (!str_starts_with($imagePath, 'assets/')) {
                $fullPath = public_path('assets/' . $imagePath);
            } else {
                $fullPath = public_path($imagePath);
            }
            
            if (!file_exists($fullPath)) {
                throw new \Exception("File not found: {$fullPath}");
            }

            // Get image info
            $imageInfo = getimagesize($fullPath);
            if (!$imageInfo) {
                return null;
            }

            $imageType = $imageInfo[2];
            
            // Load image
            $image = $this->loadImageResource($fullPath, $imageType);
            if (!$image) {
                return null;
            }

            // Get dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Add text watermark
            $watermarkText = config('app.name', 'Affixin'); // Change to your site name
            $image = $this->applyTextWatermark($image, $watermarkText, $width, $height);

            // Optional: Add logo watermark
            $logoPath = public_path('images/logo.png');
            if (file_exists($logoPath)) {
                $image = $this->applyLogoWatermark($image, $logoPath, $width, $height);
            }

            // Generate watermarked path
            $pathInfo = pathinfo($imagePath);
            $watermarkedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_wm.' . $pathInfo['extension'];
            $watermarkedFullPath = public_path($watermarkedPath);

            // Save watermarked image
            $this->saveImageResource($image, $watermarkedFullPath, $imageType);

            // Free memory
            imagedestroy($image);

            return $watermarkedPath;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Load image resource based on type
     */
    private function loadImageResource($path, $type)
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
     * Save image resource
     */
    private function saveImageResource($image, $path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $path, 90);
                break;
            case IMAGETYPE_PNG:
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
     * Apply text watermark
     */
    private function applyTextWatermark($image, $text, $width, $height)
    {
        // Calculate font size
        $fontSize = max(14, min($width, $height) * 0.035);
        
        // Calculate text dimensions (approximate)
        $textWidth = strlen($text) * ($fontSize * 0.6);
        $textHeight = $fontSize;
        
        // Position: bottom-right
        $padding = 15;
        $x = $width - $textWidth - $padding;
        $y = $height - $padding;
        
        // Add semi-transparent background
        $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 50);
        imagefilledrectangle(
            $image,
            $x - 10,
            $y - $textHeight - 8,
            $x + $textWidth + 10,
            $y + 5,
            $bgColor
        );
        
        // Add text
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        // Try to use TTF font if available
        $fontFile = public_path('fonts/arial.ttf');
        if (file_exists($fontFile)) {
            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $text);
        } else {
            // Fallback to built-in font
            imagestring($image, 5, $x, $y - $textHeight, $text, $textColor);
        }
        
        return $image;
    }

    /**
     * Apply logo watermark
     */
    private function applyLogoWatermark($image, $logoPath, $width, $height)
    {
        $logoInfo = getimagesize($logoPath);
        $logo = $this->loadImageResource($logoPath, $logoInfo[2]);
        
        if (!$logo) {
            return $image;
        }

        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        
        // Resize logo to 10% of image width
        $newLogoWidth = $width * 0.10;
        $newLogoHeight = ($logoHeight / $logoWidth) * $newLogoWidth;
        
        $resizedLogo = imagecreatetruecolor($newLogoWidth, $newLogoHeight);
        imagealphablending($resizedLogo, false);
        imagesavealpha($resizedLogo, true);
        $transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
        imagefill($resizedLogo, 0, 0, $transparent);
        imagealphablending($resizedLogo, true);
        
        imagecopyresampled(
            $resizedLogo, $logo,
            0, 0, 0, 0,
            $newLogoWidth, $newLogoHeight,
            $logoWidth, $logoHeight
        );
        
        // Position: top-left
        $padding = 15;
        imagecopy($image, $resizedLogo, $padding, $padding, 0, 0, $newLogoWidth, $newLogoHeight);
        
        imagedestroy($logo);
        imagedestroy($resizedLogo);
        
        return $image;
    }
}