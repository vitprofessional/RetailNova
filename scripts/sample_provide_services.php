<?php
// One-off script to dump a sample of provide_services rows
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProvideService;

$rows = ProvideService::orderBy('id','asc')->take(10)->get(['id','customerName','serviceName','amount','qty','rate','created_at'])->toArray();

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;