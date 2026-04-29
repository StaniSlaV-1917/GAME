<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Pay/A — админский view всех крипто-платежей.
 *
 * Только чтение + базовая статистика. Ручное confirm/cancel пока не
 * делаем (если транзакция не подтвердилась, юзер делает новый pending).
 */
class AdminPaymentController extends Controller
{
    /**
     * GET /api/admin/payments
     * ?status=pending|confirmed|expired|failed
     * ?currency=USDT_TRC20|TRX|USDT_BEP20
     * ?user_id=123
     * ?per_page=20
     */
    public function index(Request $request)
    {
        $query = Payment::query()
            ->with(['user:id,fullname,username,email'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($currency = $request->query('currency')) {
            $query->where('crypto_currency', $currency);
        }
        if ($userId = (int) $request->query('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($search = $request->query('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('fullname', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $items = $query->paginate($perPage);

        // Статистика — отдельно (быстро, отдельные count'ы)
        $stats = [
            'total_count'       => Payment::count(),
            'confirmed_count'   => Payment::where('status', 'confirmed')->count(),
            'pending_count'     => Payment::where('status', 'pending')
                                          ->where('expires_at', '>', now())->count(),
            'expired_count'     => Payment::where('status', 'expired')->count(),
            'confirmed_sum_rub' => (float) Payment::where('status', 'confirmed')->sum('amount_rub'),
        ];

        return response()->json([
            'data'  => $items->items(),
            'meta'  => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * GET /api/admin/payments/{id}
     */
    public function show(int $id)
    {
        $payment = Payment::with(['user:id,fullname,username,email,phone', 'order'])
            ->findOrFail($id);

        return response()->json($payment);
    }
}
