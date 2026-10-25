<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateBusinessUploads extends Command
{
    protected $signature = 'retailnova:migrate-business-uploads {--dry-run : List legacy logo and receipt files without copying or changing records}';
    protected $description = 'Copy legacy business logos and expense receipts into business-scoped upload locations.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $logos = 0;
        $receipts = 0;
        $missing = 0;

        foreach (DB::table('business_setups')->whereNotNull('businessLogo')->get(['id', 'businessLogo']) as $business) {
            $stored = trim((string) $business->businessLogo);
            if ($stored === '' || preg_match('~^https?://~i', $stored)
                || preg_match('~^uploads/[0-9]+/logo/[A-Za-z0-9._-]+$~', ltrim(str_replace('\\', '/', $stored), '/'))) {
                continue;
            }
            $filename = basename(preg_replace('~^uploads/business/~i', '', str_replace('\\', '/', $stored)));
            $source = public_path('uploads/business/'.$filename);
            if (!is_file($source) || (int) $business->id < 1) {
                $this->warn("Missing logo for business {$business->id}: {$filename}");
                $missing++;
                continue;
            }
            $name = Str::random(40).'.'.(pathinfo($filename, PATHINFO_EXTENSION) ?: 'jpg');
            $relative = 'uploads/'.$business->id.'/logo/'.$name;
            $this->line(($dryRun ? '[dry-run] ' : '')."business {$business->id}: {$source} → {$relative}");
            if (!$dryRun) {
                $destination = public_path('uploads/'.$business->id.'/logo');
                if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
                    $this->error("Cannot create {$destination}");
                    return self::FAILURE;
                }
                if (!copy($source, $destination.DIRECTORY_SEPARATOR.$name)) {
                    $this->error("Cannot copy logo for business {$business->id}");
                    return self::FAILURE;
                }
                DB::table('business_setups')->where('id', $business->id)->update(['businessLogo' => $relative]);
            }
            $logos++;
        }

        foreach (DB::table('expense_entries')->whereNotNull('receipt_file')->get(['id', 'businessId', 'receipt_file']) as $expense) {
            $old = trim((string) $expense->receipt_file);
            if ($old === '' || preg_match('~^[0-9]+/receipts/[A-Za-z0-9._-]+$~', $old)) continue;
            $businessId = (int) $expense->businessId;
            $legacy = ltrim(str_replace('\\', '/', $old), '/');
            // Expense receipts were historically stored under this one public-disk
            // directory. Reject URLs and traversal instead of resolving arbitrary paths.
            if (!preg_match('~^expense_receipts/[A-Za-z0-9._-]+$~', $legacy)) {
                $this->warn("Unsupported legacy receipt path for expense {$expense->id}: {$old}");
                $missing++;
                continue;
            }
            $source = Storage::disk('public')->path($legacy);
            if ($businessId < 1 || !is_file($source)) {
                $this->warn("Missing receipt for expense {$expense->id}: {$old}");
                $missing++;
                continue;
            }
            $extension = pathinfo($source, PATHINFO_EXTENSION) ?: 'bin';
            $name = Str::random(40).'.'.$extension;
            $relative = $businessId.'/receipts/'.$name;
            $this->line(($dryRun ? '[dry-run] ' : '')."expense {$expense->id}, business {$businessId}: {$legacy} → {$relative}");
            if (!$dryRun) {
                $destination = Storage::disk('local')->path($relative);
                if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0755, true) && !is_dir(dirname($destination))) {
                    $this->error('Cannot create private receipt folder.');
                    return self::FAILURE;
                }
                if (!copy($source, $destination)) {
                    $this->error("Cannot copy receipt for expense {$expense->id}");
                    return self::FAILURE;
                }
                DB::table('expense_entries')->where('id', $expense->id)->update(['receipt_file' => $relative]);
            }
            $receipts++;
        }

        $this->info(($dryRun ? 'Dry run found ' : 'Copied ')."{$logos} logo(s) and {$receipts} receipt(s); {$missing} missing file(s). Original files are retained.");
        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }
}
