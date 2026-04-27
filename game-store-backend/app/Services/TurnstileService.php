<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile — защита от ботов.
 * Frontend получает токен от виджета, бэк проверяет его здесь через API.
 *
 * Документация:
 *   https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
 *
 * В dev-окружении (когда TURNSTILE_SECRET не выставлен) проверка
 * автоматически проходит — чтоб не блокировать локальную разработку.
 */
class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Проверяет Turnstile-токен.
     * Возвращает true если успешно (или если secret не настроен — dev-режим).
     */
    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        $secret = config('services.turnstile.secret');

        // Dev-режим: secret не задан → проверка отключена.
        // Это удобно для локального dev'а без Cloudflare-аккаунта.
        if (empty($secret)) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, [
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);

            if (!$response->successful()) {
                Log::warning('Turnstile verify HTTP failure', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            $data = $response->json();
            $ok = ($data['success'] ?? false) === true;

            if (!$ok) {
                Log::info('Turnstile verify rejected', [
                    'errors' => $data['error-codes'] ?? [],
                    'ip'     => $remoteIp,
                ]);
            }

            return $ok;
        } catch (\Throwable $e) {
            // На сетевых сбоях НЕ пропускаем (fail-closed) — иначе бот сможет
            // тупо забивать запросами в момент когда CF API недоступен.
            Log::warning('Turnstile verify exception: ' . $e->getMessage());
            return false;
        }
    }
}
