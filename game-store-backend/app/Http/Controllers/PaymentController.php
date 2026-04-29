<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Payment;
use App\Services\CryptoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Pay/A — REST для крипто-платежей.
 *
 * Семантика:
 *   POST /api/payments              — создать pending (по items из корзины)
 *   GET  /api/payments/{id}         — детали платежа (для PaymentView и polling)
 *   GET  /api/payments              — мои платежи (history)
 *
 * Все эндпоинты под auth:sanctum.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly CryptoPaymentService $payments
    ) {}

    /**
     * POST /api/payments
     * Body: { items: [{ game_id: int, quantity: int }, ...] }
     *
     * Создаёт pending_payment с уникальной USDT-суммой.
     * Возвращает payment с публичными полями для PaymentView.
     */
    /**
     * GET /api/payments/currencies
     * Список валют доступных для оплаты (зависит от настроенных адресов).
     * Используется фронтом для генерации селектора.
     */
    public function currencies()
    {
        return response()->json([
            'data' => $this->payments->availableCurrencies(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.game_id'   => 'required|integer|exists:games,id',
            'items.*.quantity'  => 'required|integer|min:1|max:99',
            'currency'          => 'sometimes|string|in:USDT_TRC20,TRX,USDT_BEP20',
        ], [
            'items.required' => 'Корзина не может быть пустой.',
        ]);

        // Считаем сумму по серверным ценам (не доверяем фронту)
        $gameIds = collect($data['items'])->pluck('game_id')->all();
        $games   = Game::whereIn('id', $gameIds)->get()->keyBy('id');

        $total = 0.0;
        $snapshot = [];
        foreach ($data['items'] as $item) {
            $game = $games->get($item['game_id']);
            if (!$game) {
                throw ValidationException::withMessages([
                    'items' => ["Игра #{$item['game_id']} не найдена"],
                ]);
            }
            $price = (float) $game->price;
            $qty   = (int) $item['quantity'];
            $total += $price * $qty;
            $snapshot[] = [
                'game_id'  => $game->id,
                'title'    => $game->title,
                'price'    => $price,
                'quantity' => $qty,
            ];
        }

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'items' => ['Общая сумма должна быть больше 0'],
            ]);
        }

        try {
            $payment = $this->payments->createPending(
                user: $user,
                amountRub: $total,
                currency: $data['currency'] ?? 'USDT_TRC20',
                metadata: ['cart' => $snapshot],
                orderId: null  // в MVP не привязываемся к Order — фейк-выдача
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            Log::error('[Payment] create failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json($this->present($payment), 201);
    }

    /**
     * GET /api/payments/{id}
     * Возвращает детали + флаги статуса. Frontend poll'ит каждые 3 сек.
     * Только владелец платежа.
     */
    public function show(Request $request, int $id)
    {
        $payment = Payment::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($this->present($payment));
    }

    /**
     * GET /api/payments
     * История платежей юзера (последние 50).
     */
    public function index(Request $request)
    {
        $items = Payment::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($p) => $this->present($p));

        return response()->json(['data' => $items]);
    }

    /**
     * Сериализация Payment'а для фронта.
     * Скрываем internal-поля (exchange_rate, metadata) если не нужны.
     */
    private function present(Payment $p): array
    {
        // Если pending уже истёк фактически (по expires_at) но в БД ещё
        // status='pending' (worker не пробежался), возвращаем effective='expired'
        $effectiveStatus = $p->status;
        if ($p->status === 'pending' && $p->expires_at?->isPast()) {
            $effectiveStatus = 'expired';
        }

        return [
            'id'                => $p->id,
            'crypto_currency'   => $p->crypto_currency,
            'amount_rub'        => (float) $p->amount_rub,
            'amount_crypto'     => (float) $p->amount_crypto,
            'exchange_rate'     => (float) $p->exchange_rate,
            'recipient_address' => $p->recipient_address,
            'status'            => $effectiveStatus,
            'transaction_hash'  => $p->transaction_hash,
            'confirmations'     => (int) $p->confirmations,
            'expires_at'        => $p->expires_at?->toIso8601String(),
            'confirmed_at'      => $p->confirmed_at?->toIso8601String(),
            'created_at'        => $p->created_at?->toIso8601String(),
            'metadata'          => $p->metadata,
            'seconds_remaining' => max(
                0,
                $p->expires_at ? now()->diffInSeconds($p->expires_at, false) : 0
            ),
        ];
    }
}
