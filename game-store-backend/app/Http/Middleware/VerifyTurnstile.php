<?php

namespace App\Http\Middleware;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware проверки Cloudflare Turnstile-токена.
 *
 * Использование в маршруте:
 *   Route::post('/auth/register', ...)->middleware('turnstile');
 *
 * Frontend кладёт токен в:
 *   - тело запроса под ключом 'cf-turnstile-response' (стандарт Cloudflare),
 *   - либо в header 'X-Turnstile-Token' (для удобства Vue-fetch).
 *
 * Если токен невалиден или отсутствует → 422 с понятным сообщением.
 * В dev-окружении (без TURNSTILE_SECRET) — пропускает всё (см. TurnstileService).
 */
class VerifyTurnstile
{
    public function __construct(private TurnstileService $turnstile) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->input('cf-turnstile-response')
              ?? $request->input('turnstile_token')
              ?? $request->header('X-Turnstile-Token');

        if (!$this->turnstile->verify($token, $request->ip())) {
            return response()->json([
                'message' => 'Не пройдена проверка человечности. Подтвердите что вы не бот и попробуйте снова.',
                'error_code' => 'turnstile_failed',
            ], 422);
        }

        return $next($request);
    }
}
