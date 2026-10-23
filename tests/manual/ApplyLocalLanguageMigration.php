<?php
// Explicit local-only additive migration. No seeding, resets, or production connections.
require dirname(__DIR__,2).'/vendor/autoload.php';
$app = require dirname(__DIR__,2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$source = config('database.connections.mysql');
if (!app()->environment('local') || config('database.default') !== 'mysql'
    || ($source['database'] ?? '') !== 'retailnova_local'
    || !in_array($source['host'] ?? '', ['localhost','127.0.0.1'], true)
    || !empty($source['url']) || !empty($source['read']) || !empty($source['write'])) {
    throw new RuntimeException('Refused: requires local localhost retailnova_local without connection overrides.');
}
use Illuminate\Support\Facades\{DB, Artisan, Schema};
if (DB::selectOne('SELECT DATABASE() AS db')->db !== 'retailnova_local') {
    throw new RuntimeException('Unexpected resolved database.');
}
function historyFingerprint(): array {
    $result=[];
    foreach(['business_setups','admin_users','sale_products','invoice_items','product_serials','product_stocks','purchase_products'] as $table) {
        $hash=hash_init('sha256');$count=0;
        foreach(DB::table($table)->orderBy('id')->cursor() as $record) {
            $row=(array)$record;
            unset($row['default_locale'],$row['preferred_locale']);
            hash_update($hash,json_encode($row));$count++;
        }
        $result[$table]=[$count,hash_final($hash)];
    }
    return $result;
}
$before=historyFingerprint();
if (($argv[1] ?? '') === '--apply') {
    $exit=Artisan::call('migrate', ['--path'=>'database/migrations/2026_10_11_180000_add_language_preferences.php','--force'=>true]);
    if($exit!==0){throw new RuntimeException('Additive migration failed. Inspect local migration diagnostics.');}
} elseif (($argv[1] ?? '') !== '--check') {throw new RuntimeException('Use --check or --apply.');}
if($before!==historyFingerprint()){throw new RuntimeException('Existing record fingerprint changed.');}
$present=Schema::hasColumn('business_setups','default_locale') && Schema::hasColumn('admin_users','preferred_locale');
if($present) {
    $invalidBusinesses=DB::table('business_setups')->whereNotIn('default_locale',['en','bn'])->orWhereNull('default_locale')->count();
    $invalidUsers=DB::table('admin_users')->whereNotNull('preferred_locale')->whereNotIn('preferred_locale',['en','bn'])->count();
    if($invalidBusinesses || $invalidUsers){throw new RuntimeException('Invalid persisted locale.');}
}
echo json_encode(['database'=>'retailnova_local','language_columns_present'=>$present,
    'existing_record_fingerprints_unchanged'=>true,'tables_checked'=>count($before),
    'business_defaults'=>$present?DB::table('business_setups')->select('default_locale')->distinct()->pluck('default_locale')->all():[]],JSON_PRETTY_PRINT).PHP_EOL;
