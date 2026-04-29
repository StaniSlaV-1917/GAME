<?php

namespace Database\Seeders;

use App\Models\ReactionPalette;
use Illuminate\Database\Seeder;

/**
 * Стартовая палитра реакций в Ashenforge-стилистике.
 *
 * Phase 2 / Batch A — 8 эмодзи. Маленький набор по запросу пользователя:
 * «реакции давай сначала с маленьким набором». Расширим через админку
 * в Phase 8 (модерационные тулзы).
 *
 * is_default=true → попадают в новые посты автоматически.
 * is_default=false → видны в общей палитре, но автор должен явно
 * включить через post_allowed_reactions (Phase 2 / Batch E).
 */
class ReactionsPaletteSeeder extends Seeder
{
    public function run(): void
    {
        $palette = [
            // ── Default (5 шт — основной набор для лайков и поддержки) ──
            [
                'emoji_char'  => '🔥',
                'name'        => 'Огонь',
                'description' => 'Жаркое одобрение, горим вместе',
                'is_default'  => true,
                'sort_order'  => 1,
            ],
            [
                'emoji_char'  => '⚔',
                'name'        => 'Скрещённые мечи',
                'description' => 'За эту вылазку — моя сталь',
                'is_default'  => true,
                'sort_order'  => 2,
            ],
            [
                'emoji_char'  => '🛡',
                'name'        => 'Щит',
                'description' => 'Поддерживаю и прикрою',
                'is_default'  => true,
                'sort_order'  => 3,
            ],
            [
                'emoji_char'  => '⚡',
                'name'        => 'Молния',
                'description' => 'Быстро, мощно, точно',
                'is_default'  => true,
                'sort_order'  => 4,
            ],
            [
                'emoji_char'  => '👑',
                'name'        => 'Корона',
                'description' => 'Лучшее из лучших',
                'is_default'  => true,
                'sort_order'  => 5,
            ],

            // ── Optional (3 шт — для специфичных случаев) ──
            [
                'emoji_char'  => '🏹',
                'name'        => 'Лук',
                'description' => 'В точку, прицел верный',
                'is_default'  => false,
                'sort_order'  => 6,
            ],
            [
                'emoji_char'  => '💀',
                'name'        => 'Череп',
                'description' => 'Тёмный юмор, мрак',
                'is_default'  => false,
                'sort_order'  => 7,
            ],
            [
                'emoji_char'  => '🔨',
                'name'        => 'Молот',
                'description' => 'Кованое ремесло, мастерская работа',
                'is_default'  => false,
                'sort_order'  => 8,
            ],
        ];

        foreach ($palette as $emoji) {
            ReactionPalette::updateOrCreate(
                ['name' => $emoji['name']],
                array_merge($emoji, ['is_active' => true])
            );
        }

        $this->command->info("✓ Reactions palette seeded: " . count($palette) . " emojis");
    }
}
