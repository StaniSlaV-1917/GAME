<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
        $this->info('1. Migrating storage/app/public/* → R2 storage/*');
        $publicDisk = Storage::disk('public');
        foreach ($publicDisk->allFiles() as $relPath) {
            // .gitignore и hidden-файлы пропускаем
            if (str_starts_with(basename($relPath), '.')) {
                continue;
            }
            $r2Key = 'storage/' . $relPath;
            $this->migrateOne($r2, $r2Key, fn () => $publicDisk->get($relPath), $publicDisk->size($relPath), $dry, $force, $stats);
        }

        // ── 2. public/img/* → R2 key img/* ────────────────────────────────
        $this->info('2. Migrating public/img/* → R2 img/*');
        $imgRoot = public_path('img');
        if (is_dir($imgRoot)) {
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($imgRoot, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iter as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $relPath = str_replace($imgRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
                $r2Key = 'img/' . $relPath;
                $absPath = $file->getPathname();
                $this->migrateOne($r2, $r2Key, fn () => file_get_contents($absPath), $file->getSize(), $dry, $force, $stats);
            }
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
