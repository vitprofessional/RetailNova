<?php
// Only this additive migration may run, against the explicitly named local database.
require dirname(__DIR__,2).'/vendor/autoload.php';
$app=require dirname(__DIR__,2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB,Artisan,Schema};
$source=config('database.connections.mysql');
if(!app()->environment('local') || config('database.default')!=='mysql'
    || ($source['database']??'')!=='retailnova_local'
    || !in_array($source['host']??'',['localhost','127.0.0.1'],true)
    || !empty($source['url']) || !empty($source['read']) || !empty($source['write'])){
    throw new RuntimeException('Refused: only local localhost retailnova_local without overrides is allowed.');
}
if(DB::selectOne('SELECT DATABASE() AS db')->db!=='retailnova_local'){throw new RuntimeException('Resolved database mismatch.');}
function purchaseHistoryFingerprint(): array {
    $result=[];
    foreach(['business_setups','admin_users','customers','suppliers','sale_products','invoice_items','product_serials','product_stocks','purchase_products','purchase_returns','return_purchase_items'] as $table){
        $hash=hash_init('sha256');$count=0;
        foreach(DB::table($table)->orderBy('id')->cursor() as $record){$row=(array)$record;unset($row['purchase_invoice_id']);hash_update($hash,json_encode($row));$count++;}
        $result[$table]=['count'=>$count,'hash'=>hash_final($hash)];
    }
    return $result;
}
$before=purchaseHistoryFingerprint();
if(($argv[1]??'')==='--apply'){
    if(Artisan::call('migrate',['--path'=>'database/migrations/2026_10_12_100000_create_purchase_invoice_headers.php','--force'=>true])!==0){throw new RuntimeException('Local additive migration failed.');}
}elseif(($argv[1]??'')!=='--check'){throw new RuntimeException('Use --check or --apply.');}
if($before!==purchaseHistoryFingerprint()){throw new RuntimeException('Historical records changed.');}
$columns=DB::select("SHOW COLUMNS FROM customers WHERE Field='mail'");
echo json_encode(['database'=>'retailnova_local','header_table_present'=>Schema::hasTable('purchase_invoices'),
    'nullable_header_link_present'=>Schema::hasColumn('purchase_products','purchase_invoice_id'),
    'history_fingerprints_unchanged'=>true,'tables_checked'=>count($before),
    'historical_purchase_rows'=>$before['purchase_products']['count'],'customer_email_nullable'=>($columns[0]->Null??'')==='YES'],JSON_PRETTY_PRINT).PHP_EOL;
