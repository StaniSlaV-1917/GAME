<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Создает новый заказ на основе товаров, переданных в теле запроса.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Валидация входных данных
        $validator = Validator::make($request->all(), [
            'items'          => 'required|array|min:1',
            'items.*.game_id'  => 'required|integer|exists:games,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'items.required' => 'Корзина не может быть пустой.',
            'items.array'    => 'Неверный формат корзины.',
            'items.min'      => 'В корзине должен быть хотя бы один товар.',
            'items.*.game_id.exists' => 'Один из товаров в корзине не найден в базе данных.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422); // 422 Unprocessable Entity
        }

        $orderItems = $validator->validated()['items'];
        
        $gameIds = array_column($orderItems, 'game_id');
        $games = Game::whereIn('id', $gameIds)->get()->keyBy('id');

        $total = 0;
        $itemsData = [];

        // 2. Расчет общей стоимости и подготовка данных для заказа
        foreach ($orderItems as $item) {
            $game = $games->get($item['game_id']);
            if (!$game) {
                // Теоретически, exists:games,id уже это проверяет, но это дополнительная защита
                continue; 
            }
            
            $quantity = $item['quantity'];
            $sum = $game->price * $quantity;
            $total += $sum;

            $itemsData[] = [
                'game_id'  => $game->id,
                'quantity' => $quantity,
                'price'    => $game->price,
            ];
        }

        if ($total <= 0) {
            return response()->json(['message' => 'Не удалось рассчитать стоимость заказа.'], 400);
        }

        // 3. Транзакция для создания заказа
        DB::beginTransaction();

        try {
            $order = Order::create([
                'user_id'    => $user->id,
                'status'     => 'created', // 'pending' или 'processing' тоже хорошие варианты
                'total'      => $total,
                'order_date' => now(),
            ]);

            // Привязка элементов к заказу
            foreach ($itemsData as $item) {
                $order->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'message' => 'Заказ успешно оформлен',
                'order_id' => $order->id,
            ], 201); // 201 Created

        } catch (\Throwable $e) {
            DB::rollBack();

            // Логирование ошибки было бы полезно в реальном приложении
            // Log::error('Order creation failed: ' . $e->getMessage());

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
            ->with(['items.game' => function($query) {
                // Выбираем только нужные поля из связанной модели Game
                $query->select('id', 'title', 'image'); 
            }])
            ->get();

        return response()->json($orders);
    }
}
