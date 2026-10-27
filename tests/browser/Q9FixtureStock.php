<?php
// Mutates only the explicitly supplied disposable Q9 SQLite test database.
$database = $argv[1] ?? '';
$quantity = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT);
if ($database === '' || $quantity === false || $quantity < 0) {
    fwrite(STDERR, "Usage: php Q9FixtureStock.php <isolated-sqlite-file> <non-negative-quantity>\n");
    exit(2);
}
$resolvedDatabase = realpath($database);
$testOutputRoot = realpath(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'tests-output');
if ($resolvedDatabase === false || $testOutputRoot === false
    || basename($resolvedDatabase) !== 'quality.sqlite'
    || !str_starts_with(strtolower($resolvedDatabase), strtolower($testOutputRoot.DIRECTORY_SEPARATOR))) {
    fwrite(STDERR, "Refusing to modify anything outside tests-output/**/quality.sqlite.\n");
    exit(2);
}
$pdo = new PDO('sqlite:'.$resolvedDatabase);
$update = $pdo->prepare("UPDATE product_stocks SET currentStock = :quantity, updated_at = datetime('now') WHERE businessId = 1 AND purchaseId = '2' AND productId = '2'");
$update->execute(['quantity' => $quantity]);
if ($update->rowCount() !== 1) {
    fwrite(STDERR, "Expected the isolated Q9 purchase lot (business 1, purchase 2, product 2).\n");
    exit(1);
}
echo "isolated fixture stock set to {$quantity}\n";
