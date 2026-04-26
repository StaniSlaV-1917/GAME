<?php
/**
 * One-shot скрипт переноса данных:
 *   локальный MySQL (game_store_laravel) → Supabase Postgres
 *
 * Использование (из game-store-backend/):
 *   $env:DATABASE_URL="postgresql://postgres:ПАРОЛЬ@db.adoyjzsgtuhlzflizxrp.supabase.co:5432/postgres"
 *   php scripts/migrate-data-to-supabase.php
 *
 * Или одной строкой в PowerShell:
 *   $env:DATABASE_URL="postgresql://...";  php scripts/migrate-data-to-supabase.php
 *
 * Что делает:
 *   1. Бутстрапит Laravel (использует уже настроенное подключение mysql из .env).
 *   2. Регистрирует второе соединение `supabase` через DATABASE_URL.
 *   3. На стороне Postgres отключает session_replication_role (FK-проверки),
 *      чтобы порядок вставки родителей/детей не имел значения.
 *   4. Для каждой таблицы:
 *      - Truncate target (старые данные с прода стираются — у нас БД пустая,
 *        так что не страшно)
 *      - Копирует чанками по 100 строк
 *      - Конвертирует bool-колонки (MySQL tinyint(1) 0/1 → Postgres true/false)
 *   5. После всех вставок сбрасывает sequence'ы под MAX(id).
 *
 * Что НЕ копирует (служебные на стороне Postgres, должны быть свежими):
 *   migrations, sessions, cache, cache_locks, jobs
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ── Проверки окружения ───────────────────────────────────────
$dbUrl = getenv('DATABASE_URL') ?: ($_SERVER['DATABASE_URL'] ?? null);
if (!$dbUrl) {
    fwrite(STDERR, "❌ DATABASE_URL не задан. Запуск:\n");
    fwrite(STDERR, "   \$env:DATABASE_URL=\"postgresql://postgres:ПАРОЛЬ@db.xxx.supabase.co:5432/postgres\"\n");
    fwrite(STDERR, "   php scripts/migrate-data-to-supabase.php\n");
    exit(1);
}
foreach (['pdo_mysql', 'pdo_pgsql'] as $ext) {
    if (!extension_loaded($ext)) {
        fwrite(STDERR, "❌ Расширение {$ext} не загружено. Включи в php.ini OSPanel и перезапусти.\n");
        exit(1);
    }
}

// ── Регистрируем target-соединение ───────────────────────────
config(['database.connections.supabase' => [
    'driver'         => 'pgsql',
    'url'            => $dbUrl,
    'charset'        => 'utf8',
    'prefix'         => '',
    'prefix_indexes' => true,
    'search_path'    => 'public',
    'sslmode'        => 'require',
]]);
DB::purge('supabase');

// ── Список таблиц в порядке зависимостей FK ──────────────────
$tables = [
    'users',
    'employees',
    'games',
    'game_images',
    'mods',
    'news',
    'orders',
    'order_items',
    'reviews',
    'cart_items',
    'support_tickets',
    'passwordless_codes',
    'personal_access_tokens',
    'failed_jobs',
    'password_reset_tokens',
];

// ── Проверка соединения ──────────────────────────────────────
echo "🔌 Подключаюсь к Supabase…\n";
try {
    DB::connection('supabase')->statement("SELECT 1");
    echo "✅ Supabase OK\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "❌ Не удалось подключиться к Supabase: " . $e->getMessage() . "\n");
    exit(1);
}

echo "🔌 Локальная MySQL…\n";
try {
    DB::connection()->statement("SELECT 1");
    echo "✅ Local MySQL OK (БД: " . DB::connection()->getDatabaseName() . ")\n\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "❌ Не удалось подключиться к локальной MySQL: " . $e->getMessage() . "\n");
    exit(1);
}

// ── Карта типов колонок Postgres (для конвертации bool) ──────
echo "📋 Читаю схему Supabase для определения bool-колонок…\n";
$columnTypes = []; // [table][column] => 'boolean' | 'integer' | ...
$rows = DB::connection('supabase')->select("
    SELECT table_name, column_name, data_type
    FROM information_schema.columns
    WHERE table_schema = 'public'
");
foreach ($rows as $r) {
    $columnTypes[$r->table_name][$r->column_name] = $r->data_type;
}

// ── Перенос ──────────────────────────────────────────────────
DB::connection('supabase')->statement("SET session_replication_role = 'replica'");

$totalRows = 0;
foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        echo "⏭️  {$table}: нет в локальной MySQL, пропуск\n";
        continue;
    }
    if (!Schema::connection('supabase')->hasTable($table)) {
        echo "⚠️  {$table}: нет в Supabase (миграция не прошла?), пропуск\n";
        continue;
    }

    $count = DB::table($table)->count();
    if ($count === 0) {
        echo "⏭️  {$table}: пусто, пропуск\n";
        continue;
    }

    // Truncate target
    DB::connection('supabase')->table($table)->truncate();

    // Тянем все строки (для прототипа OK; для больших таблиц можно chunkById)
    $sourceRows = DB::table($table)->get()->map(function($r) use ($table, $columnTypes) {
        $arr = (array)$r;
        // Конвертируем bool-колонки: MySQL tinyint(1) 0/1 → PHP bool → Postgres bool
        foreach ($arr as $col => &$val) {
            $type = $columnTypes[$table][$col] ?? null;
            if ($type === 'boolean' && is_numeric($val)) {
                $val = (bool)((int)$val);
            }
        }
        return $arr;
    })->toArray();

    // Вставка чанками
    $copied = 0;
    foreach (array_chunk($sourceRows, 100) as $chunk) {
        DB::connection('supabase')->table($table)->insert($chunk);
        $copied += count($chunk);
    }

    // Sequence bump (если есть колонка id с serial/bigserial)
    if (isset($columnTypes[$table]['id'])) {
        try {
            DB::connection('supabase')->statement("
                SELECT setval(
                    pg_get_serial_sequence('{$table}', 'id'),
                    COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1),
                    true
                )
            ");
        } catch (\Throwable $e) {
            echo "   ⚠️ sequence bump для {$table} пропущен: " . $e->getMessage() . "\n";
        }
    }

    echo "✅ {$table}: {$copied} строк\n";
    $totalRows += $copied;
}

DB::connection('supabase')->statement("SET session_replication_role = 'origin'");

echo "\n🎉 Готово. Перенесено всего: {$totalRows} строк.\n";
echo "Откройте https://game-l6voba.fly.dev/api/games — там должны появиться ваши игры.\n";
