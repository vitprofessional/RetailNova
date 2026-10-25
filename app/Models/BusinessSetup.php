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

        $relative = ltrim(str_replace('\\', '/', $logo), '/');
        if (preg_match('~^uploads/'.(int) $this->id.'/logo/[a-zA-Z0-9._-]+$~', $relative)) {
            return app(\App\Support\BusinessUploads::class)->url($relative);
        }

        // Legacy uploads remain supported until the explicit migration command is run.
        $relative = preg_replace('~^uploads/business/~i', '', $relative);
        $filename = basename((string) $relative);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return null;
        }

        $path = public_path('uploads/business/' . $filename);
        // On Windows/XAMPP, PHP may report is_readable() as false for files the
        // web server can serve (notably under ACL-managed public directories).
        // Confirm the configured asset exists; the rendered image retains an
        // onerror fallback in case the web server cannot serve it.
        if (!is_file($path)) {
            return null;
        }

        return asset('uploads/business/' . rawurlencode($filename));
    }

    public function invoiceLogoFilesystemPath(): ?string
    {
        $url = $this->invoiceLogoUrl();
        if ($url === null) {
            return null;
        }
        $logo = ltrim(str_replace('\\', '/', (string) $this->businessLogo), '/');
        if (preg_match('~^uploads/[1-9][0-9]*/logo/[a-zA-Z0-9._-]+$~', $logo)) {
            return public_path($logo);
        }
        $filename = basename(preg_replace('~^uploads/business/~i', '', $logo));
        return public_path('uploads/business/'.$filename);
    }
}
