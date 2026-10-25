<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

/** Stores public, tenant-owned catalog and branding images. */
class BusinessUploads
{
    private const TYPES = ['products', 'customers', 'suppliers', 'logo'];

    /** Absolute, validated directory for one business and upload type. */
    public function path(int $businessId, string $type): string
    {
        $this->assertTarget($businessId, $type);

        return public_path('uploads/'.$businessId.'/'.$type);
    }

    /** Store a random, optimized image and a 160px thumbnail; return its public-relative path. */
    public function store(UploadedFile $file, int $businessId, string $type): string
    {
        $directory = $this->path($businessId, $type);
        File::ensureDirectoryExists($directory, 0755, true);

        if (!function_exists('imagecreatefromstring') || !function_exists('imagecreatetruecolor')) {
            $extension = strtolower($file->getClientOriginalExtension());
            $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
            $name = Str::random(40).'.'.$extension;
            $file->move($directory, $name);
            return 'uploads/'.$businessId.'/'.$type.'/'.$name;
        }

        $contents = file_get_contents($file->getRealPath());
        $image = $contents === false ? false : @imagecreatefromstring($contents);
        if ($image === false) {
            throw new InvalidArgumentException('The uploaded file is not a supported image.');
        }

        $image = $this->resize($image, 800, 800);
        $extension = function_exists('imagewebp') ? 'webp' : 'jpg';
        $name = Str::random(40).'.'.$extension;
        $target = $directory.DIRECTORY_SEPARATOR.$name;
        $this->writeImage($image, $target, $extension);
        imagedestroy($image);

        $thumbDirectory = $directory.DIRECTORY_SEPARATOR.'thumbs';
        File::ensureDirectoryExists($thumbDirectory, 0755, true);
        $thumbnail = @imagecreatefromstring(file_get_contents($target));
        if ($thumbnail !== false) {
            $thumbnail = $this->resize($thumbnail, 160, 160);
            $this->writeImage($thumbnail, $thumbDirectory.DIRECTORY_SEPARATOR.$name, $extension);
            imagedestroy($thumbnail);
        }

        return 'uploads/'.$businessId.'/'.$type.'/'.$name;
    }

    public function thumbnail(string|null $relativePath): ?string
    {
        $relativePath = $this->safeRelativePath($relativePath);
        if ($relativePath === null) {
            return null;
        }

        $directory = dirname($relativePath);
        $name = basename($relativePath);
        $thumbnail = $directory.'/thumbs/'.$name;
        return is_file(public_path($thumbnail)) ? $thumbnail : $relativePath;
    }

    public function url(string|null $relativePath, bool $thumbnail = false): ?string
    {
        $path = $thumbnail ? $this->thumbnail($relativePath) : $this->safeRelativePath($relativePath);
        return $path !== null && is_file(public_path($path)) ? asset(str_replace(' ', '%20', $path)) : null;
    }

    /** Delete both the original and its thumbnail, but only below public/uploads. */
    public function delete(string|null $relativePath): void
    {
        $relativePath = $this->safeRelativePath($relativePath);
        if ($relativePath === null) {
            return;
        }

        $absolute = public_path($relativePath);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
        $thumbnail = dirname($absolute).DIRECTORY_SEPARATOR.'thumbs'.DIRECTORY_SEPARATOR.basename($absolute);
        if (is_file($thumbnail)) {
            @unlink($thumbnail);
        }
    }

    /** Total regular-file bytes stored for a business, including thumbnails. */
    public function usage(int $businessId): int
    {
        $this->assertTarget($businessId, 'products');
        $directory = public_path('uploads/'.$businessId);
        if (!is_dir($directory)) {
            return 0;
        }

        $bytes = 0;
        foreach (File::allFiles($directory) as $file) {
            $bytes += $file->getSize();
        }
        return $bytes;
    }

    private function resize(\GdImage $image, int $maxWidth, int $maxHeight): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxWidth / max(1, $width), $maxHeight / max(1, $height));
        if ($scale >= 1) {
            return $image;
        }

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        return $resized;
    }

    private function writeImage(\GdImage $image, string $target, string $extension): void
    {
        $saved = $extension === 'webp' ? imagewebp($image, $target, 80) : imagejpeg($image, $target, 80);
        if (!$saved) {
            throw new \RuntimeException('Unable to write the optimized image.');
        }
    }

    private function safeRelativePath(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }
        $path = str_replace('\\', '/', ltrim($relativePath, '/'));
        if (!preg_match('~^uploads/[1-9][0-9]*/(?:products|customers|suppliers|logo)/[a-zA-Z0-9._-]+$~', $path)) {
            return null;
        }
        return $path;
    }

    private function assertTarget(int $businessId, string $type): void
    {
        if ($businessId < 1 || !in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Invalid business upload target.');
        }
    }
}
