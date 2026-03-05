<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Game;
use Illuminate\Http\Response;

class AdminReportController extends Controller
{
    /**
     * Экспорт отчёта по заказам в CSV.
     */
    public function exportOrders()
    {
        $fileName = 'orders_report_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $columns = [
            'ID заказа',
            'Дата заказа',
            'Пользователь (ФИО)',
            'E-mail',
            'Телефон',
            'Сумма, руб.',
            'Статус',
        ];

        $callback = function () use ($columns) {
            $handle = fopen('php://output', 'w');

            // BOM для корректной кириллицы в Excel
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Заголовки
            fputcsv($handle, $columns, ';');

            Order::with('user')->chunk(100, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    $user = $order->user;

                    $row = [
                        $order->id,
                        $order->order_date ?? '',
                        $user ? $user->fullname : '',
                        $user ? $user->email : '',
                        $user ? $user->phone : '',
                        $order->total ?? '',
                        $order->status ?? '',
                    ];

                    fputcsv($handle, $row, ';');
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, Response::HTTP_OK, $headers);
    }

    /**
     * Экспорт отчёта по пользователям в CSV.
     */
    public function exportUsers()
    {
        $fileName = 'users_report_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $columns = [
            'ID пользователя',
            'ФИО',
            'E-mail',
            'Телефон',
            'Роль',
            'Дата регистрации',
        ];

        $callback = function () use ($columns) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $columns, ';');

            User::chunk(100, function ($users) use ($handle) {
                foreach ($users as $user) {
                    $row = [
                        $user->id,
                        $user->fullname ?? '',
                        $user->email ?? '',
                        $user->phone ?? '',
                        $user->role ?? '',
                        $user->reg_date ?? '',
                    ];

                    fputcsv($handle, $row, ';');
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, Response::HTTP_OK, $headers);
    }

    /**
     * Экспорт отчёта по играм в CSV.
     */
    public function exportGames()
    {
        $fileName = 'games_report_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $columns = [
            'ID игры',
            'Название',
            'Жанр',
            'Платформа',
            'Цена, руб.',
            'Старая цена, руб.',
            'Скидка, %',
            'Рейтинг (общий)',
            'Средний рейтинг по отзывам',
            'Количество отзывов',
            'Год выхода',
            'Хит',
            'Новинка',
        ];

        $callback = function () use ($columns) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $columns, ';');

            Game::withCount('reviews')->chunk(100, function ($games) use ($handle) {
                foreach ($games as $game) {
                    $row = [
                        $game->id,
                        $game->title,
                        $game->genre,
                        $game->platform,
                        $game->price,
                        $game->old_price,
                        $game->discount_percent,
                        $game->rating,
                        $game->average_review_rating, // аксессор
                        $game->reviews_count,        // аксессор
                        $game->release_year,
                        $game->is_featured ? 'Да' : 'Нет',
                        $game->is_new ? 'Да' : 'Нет',
                    ];

                    fputcsv($handle, $row, ';');
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, Response::HTTP_OK, $headers);
    }
}
