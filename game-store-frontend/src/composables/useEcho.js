import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Laravel Echo client для подключения к Reverb (или Pusher Cloud, если
 * мигрируем в будущем — клиент тот же, отличаются только ENV).
 *
 * Singleton: создаётся однажды при первом использовании, переиспользуется.
 * Лениво — Echo не инициализируется пока компонент его не запросит,
 * чтобы пустые страницы не открывали WS-коннект к Reverb.
 *
 * Использование в Vue-компоненте:
 *   const echo = useEcho();
 *   onMounted(() => {
 *     echo.private(`chat.${roomId}`).listen('MessageSent', (e) => {
 *       messages.value.push(e.message);
 *     });
 *   });
 *   onUnmounted(() => {
 *     echo.leave(`chat.${roomId}`);
 *   });
 *
 * Auth для приватных каналов идёт через Sanctum-куку:
 *   - На бэке: routes/channels.php определяет какие каналы кто может слушать
 *   - На фронте: Echo сам POST'ит на /api/broadcasting/auth с куками,
 *     бэк проверяет роль/доступ, возвращает auth-токен для канала
 */

let _echo = null;

function buildEcho() {
  // Сразу подключаем Pusher на window — Laravel Echo использует его
  // как клиент по умолчанию (Reverb совместим с Pusher protocol)
  window.Pusher = Pusher;

  return new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // Auth для приватных каналов — берёт Sanctum-куку автоматически
    // потому что credentials: 'include' передаётся в fetch
    authEndpoint: `${import.meta.env.VITE_API_BASE_URL}/api/broadcasting/auth`,
    auth: {
      withCredentials: true,
    },
  });
}

/**
 * Возвращает singleton Echo-клиент. Лениво создаётся при первом вызове.
 */
export function useEcho() {
  if (!_echo) {
    _echo = buildEcho();
  }
  return _echo;
}

/**
 * Закрывает все WS-коннекты и сбрасывает singleton. Полезно при logout —
 * чтоб приватные каналы предыдущего юзера отключились.
 */
export function disconnectEcho() {
  if (_echo) {
    _echo.disconnect();
    _echo = null;
  }
}
