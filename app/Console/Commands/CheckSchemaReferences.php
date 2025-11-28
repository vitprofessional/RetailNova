<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckSchemaReferences extends Command
{
    protected $signature = 'app:check-schema-references {--path= : Path to scan (defaults to project root)}';

    protected $description = 'Scan migrations to build a table->columns map and report raw SQL INSERTs that reference columns not present in migrations.';

    public function handle(): int
    {
        $root = rtrim($this->option('path') ?: base_path(), DIRECTORY_SEPARATOR);

        $this->info('Building table column map from migrations...');
        $migrationDir = $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        $tables = [];

        if (!is_dir($migrationDir)) {
            $this->error('Migrations directory not found: ' . $migrationDir);
            return self::FAILURE;
        }

        $files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.php');
        foreach ($files as $f) {
            $contents = file_get_contents($f);
            if ($contents === false) continue;

            $currentTable = null;
            // split lines for a lightweight parser
            $lines = preg_split('/\r?\n/', $contents);
            foreach ($lines as $line) {
                // detect Schema::create('table' or Schema::table('table'
                if (preg_match('/Schema::(?:create|table)\(\s*["\']([a-z0-9_]+)["\']\s*,/i', $line, $m)) {
                    $currentTable = $m[1];
                    if (!isset($tables[$currentTable])) $tables[$currentTable] = [];
                }
                // detect end of closure - naive: a line with '});'
                if ($currentTable && preg_match('/^\s*\)\s*;?\s*$/', trim($line))) {
                    $currentTable = null;
                }
                if ($currentTable) {
                    // detect common column declarations: $table->string('name'), $table->unsignedBigInteger('name'), $table->id(), etc.
                    if (preg_match_all('/\$table->(?:unsignedBigInteger|unsignedInteger|bigInteger|integer|string|text|dateTime|timestamp|date|boolean|id|increments|tinyInteger|smallInteger|decimal|float|json|jsonb)\(\s*["\']([^"\']+)["\']\s*\)/i', $line, $cols)) {
                        foreach ($cols[1] as $col) {
                            $tables[$currentTable][$col] = true;
                        }
                    }
                    // detect foreign('col') patterns
                    if (preg_match_all('/->foreign\(\s*["\']([^"\']+)["\']\s*\)/i', $line, $fc)) {
                        foreach ($fc[1] as $col) {
                            $tables[$currentTable][$col] = true;
                        }
                    }
                    // detect $table->id() which implies 'id'
                    if (preg_match('/\$table->id\s*\(\s*\)/', $line)) {
                        $tables[$currentTable]['id'] = true;
                    }
                }
            }
        }

        $this->info('Collected columns for ' . count($tables) . ' tables from migrations.');

        $this->info('Scanning repository for raw INSERT statements...');
        $excluded = ['vendor', 'storage', 'node_modules', '.git'];
        $dirIter = new \RecursiveDirectoryIterator($root);
        $iter = new \RecursiveIteratorIterator($dirIter);

        $issues = [];
        foreach ($iter as $file) {
            if (!$file->isFile()) continue;
            $path = $file->getPathname();
            $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
            // skip exclusions
            foreach ($excluded as $ex) { if (strpos($rel, $ex . DIRECTORY_SEPARATOR) === 0) continue 2; }
            // only scan PHP, SQL, and blade files
            if (!preg_match('/\.(php|sql|blade\.php|js|ts)$/i', $path)) continue;
            $content = file_get_contents($path);
            if ($content === false) continue;

            // find patterns like: insert into `table` (`col1`, `col2` ...)
            if (preg_match_all('/insert\s+into\s+`?([a-z0-9_]+)`?\s*\(([^\)]+)\)/i', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $table = $m[1];
                    $cols = array_map('trim', explode(',', $m[2]));
                    $cols = array_map(function($c){ return trim($c, " `\n\r\t\0\x0B\"'"); }, $cols);
                    foreach ($cols as $c) {
                        if ($c === '') continue;
                        if (!isset($tables[$table]) || !isset($tables[$table][$c])) {
                            $issues[] = [
                                'file' => $rel,
                                'table' => $table,
                                'column' => $c,
                                'snippet' => $m[0],
                            ];
                        }
                    }
                }
            }
        }

        if (empty($issues)) {
            $this->info('No raw-INSERT column mismatches found.');
        } else {
            $this->warn('Found referenced columns not declared in migrations:');
            // group by table
            $grouped = [];
            foreach ($issues as $it) {
                $grouped[$it['table']][] = $it;
            }
            foreach ($grouped as $table => $rows) {
                $this->line('Table: ' . $table);
                $cols = [];
                foreach ($rows as $r) {
                    $cols[$r['column']] = true;
                }
                $this->line('  Missing columns: ' . implode(', ', array_keys($cols)));
                $this->line('  Occurrences:');
                foreach ($rows as $r) {
                    $this->line('    - ' . $r['file'] . ' => ' . trim($r['snippet']));
                }
            }
            $this->line('');
        }

        $this->info('Scan complete. Reminder: this scanner only checks raw SQL INSERT patterns and relies on migrations parsing; it does not catch Eloquent insert/update calls that reference missing columns via model $fillable or programmatic arrays.');

        return self::SUCCESS;
    }
}
