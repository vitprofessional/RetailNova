<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$sourcePath = $root . DIRECTORY_SEPARATOR . 'design-assets' . DIRECTORY_SEPARATOR . 'retailnova-approved-icon.png';
$outputDirectory = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'pwa';

if (!extension_loaded('gd')) {
    fwrite(STDERR, "PHP GD is required to generate the approved PWA icons.\n");
    exit(1);
}

if (!is_file($sourcePath) || ($source = @imagecreatefrompng($sourcePath)) === false) {
    fwrite(STDERR, "Approved PNG source not found or unreadable: {$sourcePath}\n");
    exit(1);
}

$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);
if ($sourceWidth !== $sourceHeight) {
    imagedestroy($source);
    fwrite(STDERR, "The approved source must be square; refusing to crop or distort it.\n");
    exit(1);
}

if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
    imagedestroy($source);
    fwrite(STDERR, "Unable to create the PWA output directory.\n");
    exit(1);
}

/** Resample the complete square source without cropping, stretching or adding rounded masks. */
$saveSquare = static function (string $filename, int $size) use ($source, $outputDirectory, $sourceWidth, $sourceHeight): void {
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, true);
    imagesavealpha($canvas, false);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $size, $size, $sourceWidth, $sourceHeight);
    $path = $outputDirectory . DIRECTORY_SEPARATOR . $filename;
    if (!imagepng($canvas, $path, 9, PNG_ALL_FILTERS)) {
        imagedestroy($canvas);
        throw new RuntimeException("Unable to write {$path}");
    }
    imagedestroy($canvas);
};

try {
    $saveSquare('icon-192.png', 192);
    $saveSquare('icon-512.png', 512);
    $saveSquare('apple-touch-icon.png', 180);

    // The mark begins close to the source canvas edge. Inset the complete rounded-square
    // artwork so its visible corners and central mark fit the maskable safe zone.
    $size = 512;
    $safeSize = (int) round($size * 0.7);
    $offset = intdiv($size - $safeSize, 2);
    $maskable = imagecreatetruecolor($size, $size);
    imagealphablending($maskable, true);
    imagesavealpha($maskable, false);
    $background = imagecolorsforindex($source, imagecolorat($source, (int) round($sourceWidth * 0.08), (int) round($sourceHeight * 0.5)));
    $navy = imagecolorallocate($maskable, $background['red'], $background['green'], $background['blue']);
    imagefill($maskable, 0, 0, $navy);

    // The approved source has white pixels outside its rounded-square tile. Remove only
    // that outer matte for the maskable variant so the brand canvas fills every mask shape;
    // the original artwork and the other three icon files remain unaltered.
    $tileInset = (int) round($sourceWidth * 0.045);
    $tileRadius = (int) round($sourceWidth * 0.19);
    $tileRight = $sourceWidth - 1 - $tileInset;
    $tileBottom = $sourceHeight - 1 - $tileInset;
    $maskedSource = imagecreatetruecolor($sourceWidth, $sourceHeight);
    imagealphablending($maskedSource, false);
    imagesavealpha($maskedSource, true);
    $transparent = imagecolorallocatealpha($maskedSource, 0, 0, 0, 127);
    imagefill($maskedSource, 0, 0, $transparent);
    imagealphablending($maskedSource, true);
    for ($y = $tileInset; $y <= $tileBottom; $y++) {
        for ($x = $tileInset; $x <= $tileRight; $x++) {
            $nearLeft = $x < $tileInset + $tileRadius;
            $nearRight = $x > $tileRight - $tileRadius;
            $nearTop = $y < $tileInset + $tileRadius;
            $nearBottom = $y > $tileBottom - $tileRadius;
            if (!(($nearLeft || $nearRight) && ($nearTop || $nearBottom))) {
                imagesetpixel($maskedSource, $x, $y, imagecolorat($source, $x, $y));
                continue;
            }

            $centerX = $nearLeft ? $tileInset + $tileRadius : $tileRight - $tileRadius;
            $centerY = $nearTop ? $tileInset + $tileRadius : $tileBottom - $tileRadius;
            $dx = $x - $centerX;
            $dy = $y - $centerY;
            if (($dx * $dx) + ($dy * $dy) <= $tileRadius * $tileRadius) {
                imagesetpixel($maskedSource, $x, $y, imagecolorat($source, $x, $y));
            }
        }
    }
    imagealphablending($maskable, true);
    imagecopyresampled($maskable, $maskedSource, $offset, $offset, 0, 0, $safeSize, $safeSize, $sourceWidth, $sourceHeight);
    imagedestroy($maskedSource);
    if (!imagepng($maskable, $outputDirectory . DIRECTORY_SEPARATOR . 'icon-maskable-512.png', 9, PNG_ALL_FILTERS)) {
        throw new RuntimeException('Unable to write icon-maskable-512.png');
    }
    imagedestroy($maskable);
} finally {
    imagedestroy($source);
}

fwrite(STDOUT, "Generated 192, 512, maskable 512, and Apple 180 PNG assets from the approved square source.\n");
