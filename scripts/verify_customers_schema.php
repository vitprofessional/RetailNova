<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function println($label, $value) {
    echo $label.': '.(is_scalar($value) ? $value : json_encode($value)).PHP_EOL;
}

try {
    $col1 = Schema::getColumnType('customers','accReceivable');
    $col2 = Schema::getColumnType('customers','accPayable');
    println('accReceivable type', $col1);
    println('accPayable type', $col2);

    $indexes = DB::select('SHOW INDEX FROM customers');
    // Filter unique
    $unique = array_values(array_filter($indexes, function ($row) { return (int)$row->Non_unique === 0; }));
    // Extract key names for mail and mobile
    $byColumn = [];
    foreach ($indexes as $row) {
        $byColumn[$row->Column_name][] = $row->Key_name;
    }
    println('unique indexes', array_values(array_unique(array_map(function($r){return $r->Key_name;}, $unique))));
    println('mail keys', $byColumn['mail'] ?? []);
    println('mobile keys', $byColumn['mobile'] ?? []);
} catch (Throwable $e) {
    echo 'Error: '.$e->getMessage().PHP_EOL;
    exit(1);
}
