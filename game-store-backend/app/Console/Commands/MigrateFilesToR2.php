<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Phase 1 / Batch E — миграция файлов с Fly volume в R2.
 *
 * Копирует:
 *   /var/www/html/storage/app/public/*  →  R2 key: storage/*
 *   /var/www/html/public/img/*          →  R2 key: img/*
 *
 * Префиксы выбраны так, чтобы существующие пути в БД
 * (game_images.path = '/storage/...') и логика frontend'а
 * (resolveMediaUrl: bare filename → /img/<name>) продолжали работать
 * без изменений в данных или коде фронта.
 *
 * Идемпотентная: если файл уже есть в R2 с тем же ключом — пропускает.
 *
 * Запуск:
 *   php artisan media:migrate-to-r2          (production-сценарий, на проде через ssh)
 *   php artisan media:migrate-to-r2 --dry    (показать что будет копировать, без записи)
 *   php artisan media:migrate-to-r2 --force  (перезаписать существующие в R2)
 */
class MigrateFilesToR2 extends Command
{
    protected $signature = 'media:migrate-to-r2
                            {--dry : Show plan without writing}
                            {--force : Overwrite files even if exist in R2}';

    protected $description = 'Migrate files from local Fly volume to Cloudflare R2';

    public function handle(): int
    {
        $r2 = Storage::disk('r2');
        $dry = (bool) $this->option('dry');
        $force = (bool) $this->option('force');

        if ($dry) {
            $this->warn('DRY RUN — ничего не записываем, только показываем план');
        }

        $stats = ['copied' => 0, 'skipped' => 0, 'failed' => 0, 'bytes' => 0];

        // ── 1. storage/app/public/* → R2 key storage/* ────────────────────
        // ВАЖНО: читаем напрямую с ФС Fly volume (не через Storage::disk('public'),
        // потому что после изменения config 'public' disk указывает на R2).
        $this->info('1. Migrating storage/app/public/* → R2 storage/*');
        $publicRoot = storage_path('app/public');
        if (is_dir($publicRoot)) {
            $this->scanAndMigrate($publicRoot, 'storage/', $r2, $dry, $force, $stats);
        } else {
            $this->warn('  storage/app/public directory not found, skipping');
        }

        // ── 2. public/img/* → R2 key img/* ────────────────────────────────
        $this->info('2. Migrating public/img/* → R2 img/*');
        $imgRoot = public_path('img');
        if (is_dir($imgRoot)) {
            $this->scanAndMigrate($imgRoot, 'img/', $r2, $dry, $force, $stats);
        } else {
            $this->warn('  public/img directory not found, skipping');
        }

        $this->newLine();
        $this->info(sprintf(
            'Done: copied=%d, skipped=%d, failed=%d, total_bytes=%s',
            $stats['copied'],
            $stats['skipped'],
            $stats['failed'],
            $this->humanBytes($stats['bytes'])
        ));

        return self::SUCCESS;
    }

    /**
     * Рекурсивно сканирует директорию на ФС и копирует все файлы в R2
     * с указанным префиксом.
     */
    protected function scanAndMigrate(string $absRoot, string $r2Prefix, $r2, bool $dry, bool $force, array &$stats): void
    {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iter as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            // Скрытые файлы (.gitignore, .DS_Store) пропускаем
            if (str_starts_with($name, '.')) {
                continue;
            }
            $absPath = $file->getPathname();
            $relPath = ltrim(str_replace($absRoot, '', $absPath), DIRECTORY_SEPARATOR);
            $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
            $r2Key = $r2Prefix . $relPath;
            $this->migrateOne($r2, $r2Key, fn () => file_get_contents($absPath), $file->getSize(), $dry, $force, $stats);
        }
    }

    protected function migrateOne($r2, string $r2Key, callable $contentReader, int $size, bool $dry, bool $force, array &$stats): void
    {
        try {
            if (!$force && $r2->exists($r2Key)) {
                $this->line("  <fg=gray>skip</> {$r2Key} <fg=gray>(already in R2)</>");
                $stats['skipped']++;
                return;
            }

            if ($dry) {
                $this->line("  <fg=yellow>plan</> {$r2Key} <fg=gray>(" . $this->humanBytes($size) . ")</>");
                return;
            }

            $r2->put($r2Key, $contentReader(), 'public');
            $this->line("  <fg=green>copy</> {$r2Key} <fg=gray>(" . $this->humanBytes($size) . ")</>");
            $stats['copied']++;
            $stats['bytes'] += $size;
        } catch (\Throwable $e) {
            $this->error("  fail {$r2Key}: " . $e->getMessage());
            $stats['failed']++;
        }
    }

    protected function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
