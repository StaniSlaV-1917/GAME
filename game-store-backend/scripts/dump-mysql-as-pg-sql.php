<?php
/**
 * Plan B: дамп локальной MySQL → готовый Postgres-SQL файл.
 *
 * Вариант когда прямое подключение к Supabase из локали не работает
 * (TLS/pooler/network). Скрипт обращается ТОЛЬКО к локальной MySQL,
 * генерирует data-import.sql который ты копируешь в Supabase
 * Dashboard → SQL Editor → Run.
 *
 * Использование:
 *   cd C:\OSPanel\domains\Games\game-store-backend
 *   php scripts/dump-mysql-as-pg-sql.php
 *   # Откроется data-import.sql в текущей папке.
 *
 * Что делает:
 *   1. Подключается к локальной MySQL (game_store_laravel из .env).
 *   2. Определяет bool-колонки по типу tinyint(1) в MySQL info_schema.
 *   3. Для каждой таблицы:
 *      - TRUNCATE ... RESTART IDENTITY CASCADE
 *      - INSERT по строке (правильное Postgres-цитирование, true/false
 *        для bool, NULL без кавычек)
 *   4. В конце — setval для всех sequence'ов.
 *
 * Не дампит: migrations, sessions, cache, cache_locks, jobs (служебные,
 * на Supabase свежие после миграций).
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = [
    'users', 'employees', 'games', 'game_images', 'mods', 'news',
    'orders', 'order_items', 'reviews', 'cart_items', 'support_tickets',
    'passwordless_codes', 'personal_access_tokens', 'failed_jobs',
    'password_reset_tokens',
];

// ── Определяем bool-колонки на стороне MySQL (tinyint(1)) ──
echo "🔌 MySQL…\n";
try {
    DB::connection()->statement("SELECT 1");
} catch (\Throwable $e) {
    fwrite(STDERR, "❌ Local MySQL: " . $e->getMessage() . "\n");
    exit(1);
}
$dbName = DB::connection()->getDatabaseName();
echo "✅ Local MySQL OK (БД: {$dbName})\n\n";

$boolColumns = []; // [table][column] => true
$rows = DB::select("
    SELECT TABLE_NAME, COLUMN_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = ? AND COLUMN_TYPE = 'tinyint(1)'
", [$dbName]);
foreach ($rows as $r) {
    $boolColumns[$r->TABLE_NAME][$r->COLUMN_NAME] = true;
}
echo "📋 Bool-колонок (tinyint(1)) найдено: " . array_sum(array_map('count', $boolColumns)) . "\n\n";

// ── Открываем выходной SQL ──
$outPath = __DIR__ . '/../data-import.sql';
$out = fopen($outPath, 'w');
fwrite($out, "-- ===================================================\n");
fwrite($out, "-- Generated " . date('c') . "\n");
fwrite($out, "-- Source: local MySQL ({$dbName})\n");
fwrite($out, "-- Target: Supabase Postgres\n");
fwrite($out, "-- Использование: открой Supabase Dashboard → SQL Editor → New query\n");
fwrite($out, "--                вставь этот файл целиком, нажми Run\n");
fwrite($out, "-- ===================================================\n\n");
fwrite($out, "BEGIN;\n");
fwrite($out, "SET session_replication_role = 'replica';\n\n");

$totalRows = 0;
foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        fwrite($out, "-- {$table}: нет в локальной MySQL\n\n");
        continue;
    }

    $count = DB::table($table)->count();
    if ($count === 0) {
        fwrite($out, "-- {$table}: пусто\n\n");
        continue;
    }

    fwrite($out, "-- ── {$table}: {$count} строк ──\n");
    fwrite($out, "TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE;\n");

    $sourceRows = DB::table($table)->get();
    $first = (array)$sourceRows->first();
    $cols = array_keys($first);
    $colsList = '"' . implode('","', $cols) . '"';

    foreach ($sourceRows as $row) {
        $arr = (array)$row;
        $values = [];
        foreach ($cols as $col) {
            $val = $arr[$col];
            if ($val === null) {
                $values[] = 'NULL';
            } elseif (isset($boolColumns[$table][$col])) {
                $values[] = ((int)$val) ? 'true' : 'false';
            } elseif (is_int($val) || (is_string($val) && ctype_digit($val))) {
                $values[] = (string)$val;
            } elseif (is_float($val) || (is_string($val) && is_numeric($val))) {
                $values[] = (string)$val;
            } else {
                // Строка: заменяем одинарные кавычки на удвоенные (Postgres-стиль)
                $escaped = str_replace("'", "''", (string)$val);
                // E'...' если есть бэкслеши/спец-символы — но обычно не нужен
                $values[] = "'" . $escaped . "'";
            }
        }
        fwrite($out, "INSERT INTO \"{$table}\" ({$colsList}) VALUES (" . implode(',', $values) . ");\n");
    }
    fwrite($out, "\n");
    $totalRows += $count;
    echo "✅ {$table}: {$count} строк\n";
}

fwrite($out, "SET session_replication_role = 'origin';\n");
fwrite($out, "COMMIT;\n\n");

fwrite($out, "-- ── Сброс sequence'ов под MAX(id) ──\n");
foreach ($tables as $table) {
    if (!Schema::hasTable($table)) continue;
    $first = DB::table($table)->first();
    if ($first && property_exists($first, 'id')) {
        fwrite($out, "SELECT setval(pg_get_serial_sequence('{$table}','id'), COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1), true);\n");
    }
}

fclose($out);

$size = round(filesize($outPath) / 1024, 1);
echo "\n🎉 Готово. {$totalRows} строк → data-import.sql ({$size} KB)\n";
echo "\n📋 Что делать дальше:\n";
echo "   1. Открой Supabase Dashboard → SQL Editor → New query\n";
echo "   2. Открой data-import.sql любым редактором, скопируй ВСЁ содержимое\n";
echo "   3. Вставь в SQL Editor, нажми Run (Ctrl+Enter)\n";
echo "   4. Проверь https://game-l6voba.fly.dev/api/games — там должны\n";
echo "      появиться твои реальные игры.\n";
