<?php
if(($argv[1]??'')!=='--prepare'){throw new RuntimeException('Explicit --prepare required.');}
$argv[1]='--check';require __DIR__.'/ApplyLocalPurchaseHeaderMigration.php';
$business=\App\Models\BusinessSetup::findOrFail(1);
$token='SPRINT02-QA-20261012';
$records=[];
foreach(['Supplier'=>'Supplier','Product A'=>'Product','Product B'=>'Product'] as $suffix=>$model){
    $class='App\\Models\\'.$model;
    $record=$class::withoutGlobalScopes()->where('name',$token.' '.$suffix)->where('businessId',$business->id)->first();
    if(!$record){$record=$class::create(['businessId'=>$business->id,'name'=>$token.' '.$suffix]);}
    $records[$suffix]=['id'=>$record->id,'businessId'=>$record->businessId,'name'=>$record->name];
}
echo json_encode(['qa_only'=>true,'records'=>$records],JSON_PRETTY_PRINT).PHP_EOL;
