<?php
/**
 * One-shot скрипт переноса данных:
 *   локальный MySQL (game_store_laravel) → Supabase Postgres
 *
 * Использование (из game-store-backend/):
 *   $env:SUPABASE_URL="postgresql://postgres.xxx:ПАРОЛЬ@aws-0-eu-central-1.pooler.supabase.com:6543/postgres"
 *   php scripts/migrate-data-to-supabase.php
 *
 * Особенности:
 *   - Используем именно SUPABASE_URL (а НЕ DATABASE_URL), чтобы Laravel'овский
 *     env('DATABASE_URL') в database.php не перехватил локальное mysql-соединение.
 *   - Лучше использовать строку из Supabase pooler-а (порт 6543 transaction
 *     mode, либо 5432 session mode) — direct connection (db.xxx.supabase.co)
 *     IPv6-only и с Windows-машин обычно не резолвится.
 *   - Не используем SET session_replication_role — pooler не любит
 *     session-state. Вместо этого копируем в правильном FK-порядке.
 *
 * Что делает:
 *   1. Бутстрапит Laravel (mysql из .env).
 *   2. Регистрирует второе соединение `supabase` через SUPABASE_URL.
 *   3. Для каждой таблицы (родители → дети):
 *      - Truncate target
 *      - Копирует чанками по 100 строк, конвертирует bool tinyint(1) → bool
 *   4. После — sequence reset под MAX(id).
 *
 * Не копируется: migrations, sessions, cache, cache_locks, jobs (служебные).
 */

// Защита от того, что DATABASE_URL может быть выставлен в окружении
// (тогда Laravel mysql-конфиг подцепит её и обманет нас, как в прошлом запуске).
putenv('DATABASE_URL');
unset($_ENV['DATABASE_URL'], $_SERVER['DATABASE_URL']);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ── Проверки окружения ───────────────────────────────────────
$dbUrl = getenv('SUPABASE_URL') ?: ($_SERVER['SUPABASE_URL'] ?? null);
if (!$dbUrl) {
    fwrite(STDERR, "❌ SUPABASE_URL не задан. Запуск:\n");
    fwrite(STDERR, "   \$env:SUPABASE_URL=\"postgresql://postgres.xxx:ПАРОЛЬ@aws-0-XX.pooler.supabase.com:6543/postgres\"\n");
    fwrite(STDERR, "   php scripts/migrate-data-to-supabase.php\n");
    fwrite(STDERR, "\nUrl бери из Supabase → Connect → Connection pooler.\n");
    exit(1);
}
foreach (['pdo_mysql', 'pdo_pgsql'] as $ext) {
    if (!extension_loaded($ext)) {
        fwrite(STDERR, "❌ Расширение {$ext} не загружено. Включи в php.ini OSPanel и перезапусти.\n");
        exit(1);
    }
}

// ── Регистрируем target-соединение ───────────────────────────
// Парсим URL вручную и передаём host/port/user/pass отдельно — Laravel'овский
// парсер URL для pgsql иногда не подхватывает sslmode корректно, и Supabase
// pooler рвёт коннект на первом же запросе после non-TLS-handshake.
$parsed = parse_url($dbUrl);
if (!$parsed || empty($parsed['host'])) {
    fwrite(STDERR, "❌ Не смог распарсить SUPABASE_URL. Проверь формат:\n");
    fwrite(STDERR, "   postgresql://USER:PASS@HOST:PORT/DBNAME\n");
    exit(1);
}
parse_str($parsed['query'] ?? '', $queryParams);

config(['database.connections.supabase' => [
    'driver'         => 'pgsql',
    'host'           => $parsed['host'],
    'port'           => $parsed['port'] ?? 5432,
    'database'       => ltrim($parsed['path'] ?? '/postgres', '/'),
    'username'       => urldecode($parsed['user'] ?? ''),
    'password'       => urldecode($parsed['pass'] ?? ''),
    'charset'        => 'utf8',
    'prefix'         => '',
    'prefix_indexes' => true,
    'search_path'    => 'public',
    'sslmode'        => $queryParams['sslmode'] ?? 'require',
]]);
DB::purge('supabase');

// Поднимаем socket-таймаут — pooler Supabase иногда долго отвечает первым
// раз после установки TLS, дефолтный 60s может не хватить на холодный handshake.
ini_set('default_socket_timeout', '120');

// ── Список таблиц в порядке зависимостей FK ──────────────────
// Родители перед детьми — тогда даже без отключения FK-проверок
// constraints не падают.
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

// ── Проверка соединений ──────────────────────────────────────
echo "🔌 Локальная MySQL…\n";
try {
    DB::connection()->statement("SELECT 1");
    $localDb = DB::connection()->getDatabaseName();
    if ($localDb === 'postgres') {
        fwrite(STDERR, "❌ Локальная mysql-сессия указывает на 'postgres' — где-то ещё перехвачен URL.\n");
        fwrite(STDERR, "   Закрой это PowerShell-окно, открой новое и повтори:\n");
        fwrite(STDERR, "      cd C:\\OSPanel\\domains\\Games\\game-store-backend\n");
        fwrite(STDERR, "      \$env:SUPABASE_URL=\"...\"\n");
        fwrite(STDERR, "      php scripts/migrate-data-to-supabase.php\n");
        exit(1);
    }
    echo "✅ Local MySQL OK (БД: {$localDb})\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "❌ Не удалось подключиться к локальной MySQL: " . $e->getMessage() . "\n");
    exit(1);
}

echo "🔌 Supabase…\n";
try {
    DB::connection('supabase')->statement("SELECT 1");
    echo "✅ Supabase OK\n\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "❌ Не удалось подключиться к Supabase: " . $e->getMessage() . "\n");
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
echo "✅ Найдено таблиц: " . count($columnTypes) . "\n\n";

// ── Перенос ──────────────────────────────────────────────────
$totalRows = 0;
foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        echo "⏭️  {$table}: нет в локальной MySQL, пропуск\n";
        continue;
    }
    if (!isset($columnTypes[$table])) {
        echo "⚠️  {$table}: нет в Supabase (миграция не прошла?), пропуск\n";
        continue;
    }

    $count = DB::table($table)->count();
    if ($count === 0) {
        echo "⏭️  {$table}: пусто, пропуск\n";
        continue;
    }

    DB::connection('supabase')->table($table)->truncate();

    $sourceRows = DB::table($table)->get()->map(function($r) use ($table, $columnTypes) {
        $arr = (array)$r;
        foreach ($arr as $col => &$val) {
            $type = $columnTypes[$table][$col] ?? null;
            if ($type === 'boolean' && is_numeric($val)) {
                $val = (bool)((int)$val);
            }
        }
        return $arr;
    })->toArray();

    $copied = 0;
    foreach (array_chunk($sourceRows, 100) as $chunk) {
        DB::connection('supabase')->table($table)->insert($chunk);
        $copied += count($chunk);
    }

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

echo "\n🎉 Готово. Перенесено всего: {$totalRows} строк.\n";
echo "Открой https://game-l6voba.fly.dev/api/games — там должны появиться твои игры.\n";
