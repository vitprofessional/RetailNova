<?php

use App\Console\Kernel;
use Database\Seeders\MobileQualityAuditSeeder;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$seeder = $app->make(MobileQualityAuditSeeder::class);
$seeder->run();
fwrite(STDOUT, json_encode($seeder->documentIds(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
