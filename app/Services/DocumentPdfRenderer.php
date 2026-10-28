<?php

namespace App\Services;

use App\Models\BusinessSetup;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Throwable;

class DocumentPdfRenderer
{
    public function response(array $receipt, ?BusinessSetup $business, string $templateType, string $paper): Response
    {
        $logo = $this->cachedLogoPath($business, $paper);
        $filename = $this->filename($receipt['document_number'] ?? 'document', $paper);
        $content = $paper === 'a4'
            ? $this->render($receipt, $business, $templateType, $paper, $logo, 'A4')
            : $this->renderThermal($receipt, $business, $templateType, $paper, $logo);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function renderThermal(array $receipt, ?BusinessSetup $business, string $templateType, string $paper, ?string $logo): string
    {
        $width = $paper === 'thermal58' ? 58 : 80;
        $html = $this->html($receipt, $business, $templateType, $paper, $logo);
        $measure = $this->newMpdf($paper, [$width, 1000]);
        $measure->SetAutoPageBreak(false, 0);
        $this->writeHtml($measure, $html, $paper);
        // The measured cursor already includes the 4 mm top margin; reserve
        // just 1 mm below the final content to keep thermal paper compact.
        $height = max(1, ceil($measure->y + 1));

        return $this->render($receipt, $business, $templateType, $paper, $logo, [$width, $height], $html);
    }

    private function render(array $receipt, ?BusinessSetup $business, string $templateType, string $paper, ?string $logo, string|array $format, ?string $html = null): string
    {
        $mpdf = $this->newMpdf($paper, $format);
        // Thermal paper height is measured in a first pass. A second automatic
        // page break can otherwise push the footer onto a mostly blank page.
        if (is_array($format)) {
            $mpdf->SetAutoPageBreak(false, 0);
        }
        $this->writeHtml($mpdf, $html ?? $this->html($receipt, $business, $templateType, $paper, $logo), $paper);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function newMpdf(string $paper, string|array $format): Mpdf
    {
        $temporaryDirectory = storage_path('app/mpdf');
        if (! is_dir($temporaryDirectory)) {
            mkdir($temporaryDirectory, 0775, true);
        }
        $fontDirectory = storage_path('fonts/hind-siliguri');
        $fontData = (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'];

        $config = [
            'mode' => 'utf-8',
            'format' => $format,
            'fontDir' => array_merge((new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [$fontDirectory]),
            'fontdata' => $fontData + [
                'hind' => [
                    'R' => 'HindSiliguri-Regular.ttf',
                    'B' => 'HindSiliguri-Bold.ttf',
                    'useOTL' => 0xFF,
                ],
            ],
            'default_font' => 'hind',
            'useSubstitutions' => true,
            'backupSubsFont' => ['dejavusans'],
            'shrink_tables_to_fit' => 0,
            'tempDir' => $temporaryDirectory,
            'margin_left' => $paper === 'a4' ? 12 : ($paper === 'thermal58' ? 5 : 4),
            'margin_right' => $paper === 'a4' ? 12 : ($paper === 'thermal58' ? 5 : 4),
            'margin_top' => $paper === 'a4' ? 12 : 4,
            'margin_bottom' => $paper === 'a4' ? 12 : 0,
        ];

        if ($paper === 'a4') {
            $config['setAutoTopMargin'] = 'stretch';
            $config['setAutoBottomMargin'] = 'stretch';
        }

        return new Mpdf($config);
    }

    private function html(array $receipt, ?BusinessSetup $business, string $templateType, string $paper, ?string $logo): string
    {
        return view('documents.pdf-table', [
            'receipt' => $receipt,
            'business' => $business,
            'templateType' => $templateType,
            'paper' => $paper,
            'logo' => $logo,
        ])->render();
    }

    private function writeHtml(Mpdf $mpdf, string $html, string $paper): void
    {
        $bytes = strlen($html);
        Log::debug('mPDF document HTML size', ['bytes' => $bytes, 'paper' => $paper]);
        if ($bytes > 500 * 1024) {
            Log::warning('mPDF document HTML exceeds 500 KB', ['bytes' => $bytes, 'paper' => $paper]);
        }

        $mpdf->WriteHTML($html);
    }

    private function cachedLogoPath(?BusinessSetup $business, string $paper): ?string
    {
        if (! $business) {
            return null;
        }

        $cacheSize = $paper === 'a4' ? 'a4' : 'thermal';
        $maxWidth = $paper === 'a4' ? 600 : 300;
        $temporaryPath = null;
        $lockHandle = null;

        try {
            $sourcePath = $business->invoiceLogoFilesystemPath();
            if (! $sourcePath || ! is_file($sourcePath)) {
                return null;
            }

            $mtime = filemtime($sourcePath);
            if ($mtime === false) {
                throw new \RuntimeException('Could not read the configured logo modification time.');
            }
            $cacheDirectory = storage_path('app/pdf-logos');
            if (! is_dir($cacheDirectory) && ! mkdir($cacheDirectory, 0775, true) && ! is_dir($cacheDirectory)) {
                throw new \RuntimeException('Could not create the PDF logo cache directory.');
            }
            $sourceHash = hash('sha256', $sourcePath.'|'.$mtime);
            $cachePath = $cacheDirectory.'/'.$business->id.'-'.$sourceHash.'-'.$cacheSize.'.png';
            if (is_file($cachePath) && filesize($cachePath) > 0) {
                return $cachePath;
            }

            $lockHandle = fopen($cachePath.'.lock', 'c');
            if ($lockHandle === false || ! flock($lockHandle, LOCK_EX)) {
                throw new \RuntimeException('Could not lock the PDF logo cache.');
            }
            if (is_file($cachePath) && filesize($cachePath) > 0) {
                return $cachePath;
            }

            if (! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
                throw new \RuntimeException('GD is unavailable for PDF logo resizing.');
            }
            $imageInfo = getimagesize($sourcePath);
            if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
                throw new \RuntimeException('The configured logo is not a readable image.');
            }
            [$sourceWidth, $sourceHeight] = $imageInfo;
            if ($sourceWidth * $sourceHeight > 30_000_000) {
                throw new \RuntimeException('The configured logo exceeds the safe pixel limit.');
            }

            $previousMemoryLimit = (string) ini_get('memory_limit');
            $raisedMemoryLimit = false;
            $estimatedMemory = memory_get_usage(true) + ($sourceWidth * $sourceHeight * 4) + 24 * 1024 * 1024;
            $currentMemoryLimit = $this->memoryLimitBytes($previousMemoryLimit);
            if ($currentMemoryLimit !== null && $estimatedMemory > $currentMemoryLimit) {
                $raisedMemoryLimit = ini_set('memory_limit', '256M') !== false;
                if (! $raisedMemoryLimit || $this->memoryLimitBytes((string) ini_get('memory_limit')) < $estimatedMemory) {
                    throw new \RuntimeException('Insufficient PHP memory to safely resize the configured logo.');
                }
            }

            $bytes = null;
            $source = null;
            $target = null;
            $errorHandlerSet = false;
            try {
                set_error_handler(static function (int $severity, string $message): never {
                    throw new \ErrorException($message, 0, $severity);
                }, E_WARNING | E_NOTICE);
                $errorHandlerSet = true;

                $bytes = file_get_contents($sourcePath);
                if ($bytes === false || $bytes === '') {
                    throw new \RuntimeException('Could not read the configured logo file.');
                }

                $source = imagecreatefromstring($bytes);
                if ($source === false) {
                    throw new \RuntimeException('GD could not decode the configured logo.');
                }
                $scale = min(1, $maxWidth / $sourceWidth);
                $targetWidth = max(1, (int) round($sourceWidth * $scale));
                $targetHeight = max(1, (int) round($sourceHeight * $scale));
                $target = imagecreatetruecolor($targetWidth, $targetHeight);
                if ($target === false) {
                    throw new \RuntimeException('GD could not allocate the resized logo.');
                }

                imagealphablending($target, false);
                imagesavealpha($target, true);
                $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
                imagefill($target, 0, 0, $transparent);
                if (! imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight)) {
                    throw new \RuntimeException('GD could not resize the configured logo.');
                }
                if ($cacheSize === 'thermal' && ! imagefilter($target, IMG_FILTER_GRAYSCALE)) {
                    throw new \RuntimeException('GD could not convert the thermal logo to grayscale.');
                }

                $temporaryPath = $cachePath.'.'.bin2hex(random_bytes(6)).'.tmp';
                if (! imagepng($target, $temporaryPath, 6)) {
                    throw new \RuntimeException('GD could not write the resized PDF logo.');
                }
            } finally {
                if ($source instanceof \GdImage) {
                    imagedestroy($source);
                }
                if ($target instanceof \GdImage) {
                    imagedestroy($target);
                }
                unset($bytes);
                if ($errorHandlerSet) {
                    restore_error_handler();
                    $errorHandlerSet = false;
                }
                if ($raisedMemoryLimit) {
                    $previousMemoryBytes = $this->memoryLimitBytes($previousMemoryLimit);
                    // A preceding mPDF render may already have consumed more
                    // than the original limit in a long-running test worker.
                    // Restore when safe; PHP rejects lowering below current
                    // usage, so leave the request-local limit alone otherwise.
                    if ($previousMemoryBytes === null || memory_get_usage(false) < $previousMemoryBytes) {
                        @ini_set('memory_limit', $previousMemoryLimit);
                    }
                }
            }

            if (! rename($temporaryPath, $cachePath)) {
                throw new \RuntimeException('Could not finalize the resized PDF logo cache.');
            }
            $temporaryPath = null;

            return $cachePath;
        } catch (Throwable $exception) {
            if ($temporaryPath && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
            Log::warning('Could not prepare business logo for PDF; rendering without logo.', [
                'business_id' => $business->id,
                'paper' => $paper,
                'reason' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            if (is_resource($lockHandle)) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        }
    }

    private function filename(string $number, string $paper): string
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9_-]+/', '-', $number) ?: 'document';
        $suffix = match ($paper) {
            'thermal80' => '-80mm',
            'thermal58' => '-58mm',
            default => '',
        };

        return $safeNumber.$suffix.'.pdf';
    }

    private function memoryLimitBytes(string $limit): ?int
    {
        if ($limit === '' || $limit === '-1') {
            return null;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
