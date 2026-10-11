<?php
// Run explicitly: php tests/manual/SalesSerialMysqlConcurrency.php --run
// Local tables are read only; writes use a newly created, token-owned scratch schema.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$source = config('database.connections.mysql');
if (!app()->environment('local') || config('database.default') !== 'mysql'
    || ($source['database'] ?? '') !== 'retailnova_local'
    || !in_array($source['host'] ?? '', ['localhost', '127.0.0.1'], true)
    || !empty($source['url']) || !empty($source['read']) || !empty($source['write'])) {
    throw new RuntimeException('Refused: only authorized localhost retailnova_local configuration is permitted.');
}
use Illuminate\Support\Facades\DB;
use App\Models\{AdminUser, SaleProduct};
use App\Services\{SalesSerialPolicy, StockService};

function scratch(string $name, string $token, array $source): void {
    if (!preg_match('/^retailnova_serial_test_[a-f0-9]{16}$/D', $name)
        || $name !== 'retailnova_serial_test_'.$token) { throw new RuntimeException('Invalid scratch target.'); }
    $source['database'] = $name;
    config(['database.default' => 'mysql', 'database.connections' => ['mysql' => $source],
        'audit.enabled' => false, 'cache.default' => 'array', 'session.driver' => 'array',
        'mail.default' => 'array', 'queue.default' => 'sync', 'services' => []]);
    DB::purge('mysql');
    if (DB::selectOne('SELECT DATABASE() AS db')->db !== $name
        || DB::table('verification_owner')->value('token') !== $token) {
        throw new RuntimeException('Scratch ownership verification failed.');
    }
    $actor = new AdminUser(['role' => 'superadmin', 'businessId' => 1]);
    $actor->id = 1;
    auth('admin')->setUser($actor);
}
function signalPath(string $token, string $mode, string $side, string $kind): string {
    return dirname(__DIR__, 2)."/storage/framework/serial-check-$token-$mode-$side-$kind.json";
}
function waitSignal(string $path): void {
    $deadline = microtime(true) + 15;
    while (!is_file($path)) {
        if (microtime(true) > $deadline) { throw new RuntimeException('Worker synchronization timeout.'); }
        usleep(20000);
    }
}
if (($argv[1] ?? '') === '--worker') {
    [$name, $token, $mode, $side] = array_slice($argv, 2);
    scratch($name, $token, $source);
    DB::statement('SET SESSION innodb_lock_wait_timeout = 10');
    $started = microtime(true);
    file_put_contents(signalPath($token, $mode, $side, 'started'), json_encode(['at' => $started]));
    try {
        DB::transaction(function () use ($token, $mode, $side) {
            $sale = SaleProduct::findOrFail($side === 'A' ? 1 : 2);
            app(SalesSerialPolicy::class)->sync($sale, 1, 1, $mode === 'manual' ? [1] : [], [], 'serialId.0');
            if (!app(StockService::class)->decreaseStockForSale(1, 1)) { throw new RuntimeException('Insufficient stock'); }
            if ($side === 'A') {
                file_put_contents(signalPath($token, $mode, 'A', 'locked'), '{}');
                waitSignal(signalPath($token, $mode, 'B', 'started'));
                usleep(600000); // Hold real InnoDB locks while the second process attempts allocation.
            }
        });
        $result = ['status' => 'committed', 'started' => $started, 'finished' => microtime(true)];
    } catch (Illuminate\Validation\ValidationException $e) {
        $result = ['status' => 'rejected', 'started' => $started, 'finished' => microtime(true)];
    }
    file_put_contents(signalPath($token, $mode, $side, 'result'), json_encode($result));
    exit;
}
if (($argv[1] ?? '') !== '--run') { throw new RuntimeException('Use --run for explicit local scratch verification.'); }
$dsn = 'mysql:host='.$source['host'].';port='.($source['port'] ?? 3306).';charset=utf8mb4';
$server = new PDO($dsn, $source['username'], $source['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$local = new PDO($dsn.';dbname=retailnova_local', $source['username'], $source['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
function fingerprint(PDO $pdo): array {
    $result = [];
    foreach (['business_setups','sale_products','invoice_items','product_serials','product_stocks','purchase_products','migrations'] as $table) {
        $hash = hash_init('sha256'); $count = 0;
        $query = $pdo->query("SELECT * FROM `$table` ORDER BY id");
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) { hash_update($hash, json_encode($row)); $count++; }
        $result[$table] = [$count, hash_final($hash)];
    }
    return $result;
}
$before = fingerprint($local);
$column = $local->query("SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='retailnova_local' AND TABLE_NAME='business_setups' AND COLUMN_NAME='sales_serial_selection_required'")->fetchColumn();
if ((int)$column !== 1) { throw new RuntimeException('Mandatory migration default is not enabled.'); }
$disabled = $local->query('SELECT COUNT(*) FROM business_setups WHERE sales_serial_selection_required <> 1 OR sales_serial_selection_required IS NULL')->fetchColumn();
$orphans = $local->query('SELECT COUNT(*) FROM purchase_products p LEFT JOIN business_setups b ON b.id=p.businessId WHERE p.businessId IS NOT NULL AND p.businessId>0 AND b.id IS NULL')->fetchColumn();
echo "Local DB audit: default=true; disabled/null business settings=$disabled; orphan purchase business IDs=$orphans.\n";
echo 'Server version: '.$server->query('SELECT VERSION()')->fetchColumn()."\n";
$token = bin2hex(random_bytes(8));
$name = 'retailnova_serial_test_'.$token;
$created = false; $workers = []; $files = [];
try {
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $server->exec("CREATE TABLE `$name`.verification_owner (token VARCHAR(16) PRIMARY KEY) ENGINE=InnoDB");
    $stmt = $server->prepare("INSERT INTO `$name`.verification_owner VALUES (?)"); $stmt->execute([$token]);
    scratch($name, $token, $source);
    // Minimal schema exercises the real policy and StockService under InnoDB, without modifying application schemas.
    foreach ([
        'business_setups' => 'id BIGINT PRIMARY KEY, sales_serial_selection_required BOOLEAN NOT NULL DEFAULT TRUE',
        'products' => 'id BIGINT PRIMARY KEY, businessId BIGINT',
        'purchase_products' => 'id BIGINT PRIMARY KEY, productName BIGINT, businessId BIGINT',
        'product_stocks' => 'id BIGINT PRIMARY KEY, productId BIGINT, purchaseId BIGINT, businessId BIGINT, currentStock INT, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL',
        'product_serials' => 'id BIGINT PRIMARY KEY, productId BIGINT, purchaseId BIGINT, serialNumber VARCHAR(100), saleId BIGINT NULL, status VARCHAR(32) NULL, sold_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL',
        'sale_products' => 'id BIGINT PRIMARY KEY, businessId BIGINT',
    ] as $table => $definition) { DB::statement("CREATE TABLE `$table` ($definition) ENGINE=InnoDB"); }
    DB::table('business_setups')->insert(['id' => 1]);
    DB::table('products')->insert(['id' => 1, 'businessId' => 1]);
    DB::table('purchase_products')->insert(['id' => 1, 'productName' => 1, 'businessId' => 1]);
    DB::table('product_stocks')->insert(['id' => 1, 'productId' => 1, 'purchaseId' => 1, 'businessId' => 1, 'currentStock' => 1]);
    DB::table('product_serials')->insert(['id' => 1, 'productId' => 1, 'purchaseId' => 1, 'serialNumber' => 'CONCURRENCY-1', 'status' => 'available']);
    DB::table('sale_products')->insert([['id' => 1, 'businessId' => 1], ['id' => 2, 'businessId' => 1]]);
    $checks = 0;
    foreach (['auto', 'manual'] as $mode) {
        DB::table('business_setups')->update(['sales_serial_selection_required' => $mode === 'manual']);
        DB::table('product_stocks')->update(['currentStock' => 1]);
        DB::table('product_serials')->update(['saleId' => null, 'status' => 'available', 'sold_at' => null]);
        foreach (['A', 'B'] as $side) {
            foreach (['started', 'locked', 'result'] as $kind) { $files[] = signalPath($token, $mode, $side, $kind); }
            $pipes = [];
            $worker = proc_open([PHP_BINARY, __FILE__, '--worker', $name, $token, $mode, $side],
                [['file','NUL','r'], ['file','NUL','w'], ['file','NUL','w']], $pipes, dirname(__DIR__, 2), null, ['bypass_shell' => true]);
            if (!is_resource($worker)) { throw new RuntimeException('Worker did not start.'); }
            $workers[] = $worker;
            if ($side === 'A') { waitSignal(signalPath($token, $mode, 'A', 'locked')); }
        }
        foreach (['A','B'] as $side) { waitSignal(signalPath($token, $mode, $side, 'result')); }
        $a = json_decode(file_get_contents(signalPath($token, $mode, 'A', 'result')), true);
        $b = json_decode(file_get_contents(signalPath($token, $mode, 'B', 'result')), true);
        foreach ([$a['status'] === 'committed', $b['status'] === 'rejected', $b['started'] < $a['finished'],
            $b['finished'] - $b['started'] >= .5, (int)DB::table('product_stocks')->value('currentStock') === 0,
            (int)DB::table('product_serials')->value('saleId') === 1,
            DB::table('product_serials')->value('status') === 'sold'] as $pass) {
            if (!$pass) { throw new RuntimeException('Concurrency assertion failed.'); } $checks++;
        }
        echo "PASS InnoDB $mode conflict: overlapping processes; first committed; second rejected; stock=0; one allocation.\n";
    }
    if ($before !== fingerprint($local)) { throw new RuntimeException('Local data changed during verification.'); }
    echo "PASS local historical/settings data unchanged across verification (7 table fingerprints).\n";
    echo "2 real InnoDB concurrency scenarios, $checks assertions passed.\n";
} finally {
    foreach ($workers as $worker) { if (is_resource($worker)) { proc_terminate($worker); proc_close($worker); } }
    DB::disconnect('mysql');
    // Drop only the exact schema successfully created by this run and verified by its random ownership token.
    if ($created && $server->query("SELECT token FROM `$name`.verification_owner")->fetchColumn() === $token) {
        $server->exec("DROP DATABASE `$name`");
        echo "Owned scratch schema removed.\n";
    }
    foreach ($files as $file) { if (is_file($file)) { unlink($file); } }
}
