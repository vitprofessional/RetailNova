<?php

namespace App\Services;

use App\Models\BusinessSetup;
use Illuminate\Http\Response;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class DocumentPdfRenderer
{
    public function response(array $receipt, ?BusinessSetup $business, string $templateType, string $paper): Response
    {
        $logo = $this->logoDataUri($business);
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
        $measure = $this->newMpdf($paper, [$width, 1000]);
        $measure->SetAutoPageBreak(false, 0);
        $measure->WriteHTML($this->html($receipt, $business, $templateType, $paper, $logo));
        // The measured cursor already includes the 4 mm top margin; reserve
        // just 1 mm below the final content to keep thermal paper compact.
        $height = max(1, ceil($measure->y + 1));

        return $this->render($receipt, $business, $templateType, $paper, $logo, [$width, $height]);
    }

    private function render(array $receipt, ?BusinessSetup $business, string $templateType, string $paper, ?string $logo, string|array $format): string
    {
        $mpdf = $this->newMpdf($paper, $format);
        // Thermal paper height is measured in a first pass. A second automatic
        // page break can otherwise push the footer onto a mostly blank page.
        if (is_array($format)) {
            $mpdf->SetAutoPageBreak(false, 0);
        }
        $mpdf->WriteHTML($this->html($receipt, $business, $templateType, $paper, $logo));

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

    private function logoDataUri(?BusinessSetup $business): ?string
    {
        $path = $business?->invoiceLogoFilesystemPath();
        if (! $path || ! is_file($path)) {
            return null;
        }
        $mime = mime_content_type($path);
        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
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
}
