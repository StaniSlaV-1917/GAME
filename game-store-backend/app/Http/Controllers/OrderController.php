<?php

namespace App\Http\Controllers;

use App\Mail\OrderCreatedMail;
use App\Models\Game;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Создает новый заказ, резервирует и выдаёт ключи активации.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Валидация входных данных
        $validator = Validator::make($request->all(), [
            'items'              => 'required|array|min:1',
            'items.*.game_id'    => 'required|integer|exists:games,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ], [
            'items.required'             => 'Корзина не может быть пустой.',
            'items.array'                => 'Неверный формат корзины.',
            'items.min'                  => 'В корзине должен быть хотя бы один товар.',
            'items.*.game_id.exists'     => 'Один из товаров в корзине не найден в базе данных.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $orderItems = $validator->validated()['items'];

        $gameIds = array_column($orderItems, 'game_id');
        $games = Game::whereIn('id', $gameIds)
            ->withCount([
                'keys as total_keys_count',
                'keys as available_keys_count' => fn($q) => $q->where('is_issued', false),
            ])
            ->get()
            ->keyBy('id');

        // 2. Расчёт стоимости и подготовка данных
        $total = 0;
        $itemsData = [];

        foreach ($orderItems as $item) {
            $game = $games->get($item['game_id']);
            if (!$game) {
                continue;
            }

            $quantity = $item['quantity'];
            $total += $game->price * $quantity;

            $itemsData[] = [
                'game_id'  => $game->id,
                'quantity' => $quantity,
                'price'    => $game->price,
            ];
        }

        if ($total <= 0) {
            return response()->json(['message' => 'Не удалось рассчитать стоимость заказа.'], 400);
        }

        // 3. Транзакция: резервирование ключей + создание заказа
        DB::beginTransaction();

        try {
            // Резервируем ключи с блокировкой строк (защита от race condition)
            $keysToIssue = []; // game_id => GameKey

            foreach ($itemsData as $item) {
                $game = $games->get($item['game_id']);

                // Если у игры нет управляемых ключей — пропускаем (in_stock = true по умолчанию)
                if ($game->total_keys_count === 0) {
                    continue;
                }

                // Ключи управляются — берём первый свободный с блокировкой
                $key = $game->keys()
                    ->where('is_issued', false)
                    ->lockForUpdate()
                    ->first();

                if (!$key) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Игра «{$game->title}» закончилась. Пожалуйста, удалите её из корзины.",
                    ], 422);
                }

                $keysToIssue[$game->id] = $key;
            }

            // Создаём заказ
            $order = Order::create([
                'user_id'    => $user->id,
                'status'     => 'created',
                'total'      => $total,
                'order_date' => now(),
            ]);

            // Создаём позиции заказа и выдаём ключи
            foreach ($itemsData as $item) {
                $orderItem = $order->items()->create($item);

                if (isset($keysToIssue[$item['game_id']])) {
                    $key = $keysToIssue[$item['game_id']];
                    $key->is_issued     = true;
                    $key->order_item_id = $orderItem->id;
                    $key->save();
                }
            }

            DB::commit();

            // Отправляем email-уведомление (не прерываем ответ при ошибке)
            try {
                if ($user->notify_order_created !== false) {
                    $order->load('items.game');
                    Mail::to($user->email)->send(new OrderCreatedMail($order, $user->fullname ?? ''));
                }
            } catch (\Throwable $e) {
                // письмо не отправилось — заказ всё равно создан
            }

            return response()->json([
                'message'  => 'Заказ успешно оформлен',
                'order_id' => $order->id,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Внутренняя ошибка сервера при создании заказа.',
            ], 500);
        }
    }

    /**
     * Возвращает историю заказов текущего пользователя.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->orderByDesc('order_date')
            ->with(['items' => function ($query) {
                $query->with([
                    'game:id,title,image',
                    'key:id,order_item_id,key_code',
                ]);
            }])
            ->get();

        return response()->json($orders);
    }
}
