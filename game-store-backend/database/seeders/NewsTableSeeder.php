<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NewsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('news')->insert([
            [
                'title' => 'Большая летняя распродажа началась!',
                'content' => '<p>Не пропустите грандиозную летнюю распродажу в GameStore! Скидки до <strong>75%</strong> на сотни популярных игр, включая хиты этого года. Самое время пополнить свою библиотеку!</p><p>В акции участвуют такие игры, как <em>Cyberpunk 2077</em>, <em>The Witcher 3</em>, <em>Red Dead Redemption 2</em> и многие другие. Заходите в наш каталог и выбирайте!</p>',
                'image_url' => 'https://example.com/images/summer-sale.jpg',
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'title' => 'Анонс GameStore 2.0: Новый дизайн и функции',
                'content' => '<p>Мы рады объявить, что наша команда усердно работает над следующим крупным обновлением нашего магазина — <strong>GameStore 2.0</strong>.</p><p>Вас ждёт полностью переработанный интерфейс, улучшенная система рекомендаций, а также долгожданная поддержка пользовательских списков желаемого. Следите за новостями, скоро мы поделимся новыми подробностями!</p>',
                'image_url' => 'https://example.com/images/gamestore-v2.jpg',
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1),
            ],
        ]);
    }
}
