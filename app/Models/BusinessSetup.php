<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetup extends Model
{
    protected $fillable = [
        'businessName','businessLocation','mobile','email','tinCert','invoiceFooter','website','facebook','twitter','youtube','linkedin','businessLogo',
        'businessType',
        'currencySymbol','currencyPosition','currencyNegParentheses',
        'hide_invoice_acknowledgement',
        'invoice_terms_enabled',
        'invoice_terms_text', 'sales_serial_selection_required', 'default_locale'
    ];

    protected $casts = [
        'sales_serial_selection_required' => 'boolean',
        'currencyNegParentheses' => 'boolean',
        'hide_invoice_acknowledgement' => 'boolean',
        'invoice_terms_enabled' => 'boolean',
    ];

    /** Return a safe, browser-accessible URL for the configured public business logo. */
    public function invoiceLogoUrl(): ?string
    {
        $logo = trim((string) $this->businessLogo);
        if ($logo === '') {
            return null;
        }

        if (filter_var($logo, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($logo, PHP_URL_SCHEME));
            return in_array($scheme, ['http', 'https'], true) ? $logo : null;
        }

        // Uploads are stored as public/uploads/business/<filename>. Accept a legacy
        // prefixed value but never let a configured path escape that directory.
        $relative = ltrim(str_replace('\\', '/', $logo), '/');
        $relative = preg_replace('~^uploads/business/~i', '', $relative);
        $filename = basename((string) $relative);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return null;
        }

        $path = public_path('uploads/business/' . $filename);
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        return asset('uploads/business/' . rawurlencode($filename));
    }
}
