<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\EmailChangeMail;
use App\Mail\LoginCodeMail;
use App\Mail\LoginNotificationMail;
use App\Mail\PasswordResetMail;

class AuthController extends Controller
{
    /**
     * Формирует стандартизированный ответ с данными пользователя.
     */
    private function fullUserResponse(User $user): array
    {
        return [
            'id'                   => $user->id,
            'fullname'             => $user->fullname,
            'username'             => $user->username,
            'email'                => $user->email,
            'phone'                => $user->phone,
            'is_admin'             => $user->role === 'admin',
            'role'                 => $user->role,
            'reg_date'             => $user->reg_date,
            'avatar'               => $user->avatar,
            'notify_login'           => (bool) $user->notify_login,
            'notify_order_created'   => (bool) $user->notify_order_created,
            'notify_order_status'    => (bool) $user->notify_order_status,
            'notify_email_comment'   => (bool) ($user->notify_email_comment   ?? true),
            'notify_email_reply'     => (bool) ($user->notify_email_reply     ?? true),
            'notify_email_reaction'  => (bool) ($user->notify_email_reaction  ?? true),
            'notify_email_follower'  => (bool) ($user->notify_email_follower  ?? true),
            'library_public'         => (bool) ($user->library_public ?? true),
            // Модерационный статус — фронт может показать «вы заморожены»
            // если бэк отдаст это (для забаненных юзер не получит токен).
            'banned_at'            => $user->banned_at,
            'ban_reason'           => $user->ban_reason,
            'frozen_at'            => $user->frozen_at,
            'freeze_reason'        => $user->freeze_reason,
        ];
    }

    /**
     * Парсит User-Agent и возвращает ['browser' => ..., 'os' => ...]
     */
    private function parseUserAgent(string $ua): array
    {
        // Browser
        if (str_contains($ua, 'YaBrowser'))        $browser = 'Яндекс.Браузер';
        elseif (str_contains($ua, 'Edg/'))         $browser = 'Microsoft Edge';
        elseif (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')) $browser = 'Opera';
        elseif (str_contains($ua, 'Chrome/'))      $browser = 'Google Chrome';
        elseif (str_contains($ua, 'Firefox/'))     $browser = 'Mozilla Firefox';
        elseif (str_contains($ua, 'Safari/'))      $browser = 'Safari';
        else                                        $browser = 'Неизвестный браузер';

        // OS
        if (str_contains($ua, 'Windows NT 10') || str_contains($ua, 'Windows NT 11')) $os = 'Windows 10 / 11';
        elseif (str_contains($ua, 'Windows NT 6.3'))  $os = 'Windows 8.1';
        elseif (str_contains($ua, 'Windows NT 6.1'))  $os = 'Windows 7';
        elseif (str_contains($ua, 'Windows'))         $os = 'Windows';
        elseif (str_contains($ua, 'iPhone'))           $os = 'iPhone (iOS)';
        elseif (str_contains($ua, 'iPad'))             $os = 'iPad (iOS)';
        elseif (str_contains($ua, 'Android'))          $os = 'Android';
        elseif (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS X')) $os = 'macOS';
        elseif (str_contains($ua, 'Linux'))            $os = 'Linux';
        else                                           $os = 'Неизвестная ОС';

        return ['browser' => $browser, 'os' => $os];
    }

    /**
     * Отправляет уведомление о входе, если пользователь включил эту опцию.
     */
    private function sendLoginNotification(User $user, $request, string $method = 'password'): void
    {
        if (! $user->notify_login) return;
        try {
            $ua     = $request->userAgent() ?? 'Unknown';
            $parsed = $this->parseUserAgent($ua);
            $ip     = $request->ip() ?? '—';
            $time   = now()->setTimezone('Europe/Moscow')->format('d.m.Y в H:i:s') . ' (МСК)';
            Mail::to($user->email)->send(new LoginNotificationMail(
                $user->fullname ?: 'Игрок',
                $ip,
                $parsed['browser'],
                $parsed['os'],
                $time,
                $method
            ));
        } catch (\Throwable $e) {
            Log::warning('Login notification mail failed: ' . $e->getMessage());
        }
    }

    // POST /api/auth/register
    public function register(Request $request)
    {
        $data = $request->validate([
            'fullname' => 'required|string|max:255',
            'email'    => 'required|email',
            'phone'    => 'required|regex:/^7[0-9]{10}$/',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $emailHash = hash('sha256', $data['email']);
        $phoneHash = hash('sha256', $data['phone']);

        if (User::where('email_hash', $emailHash)
                ->orWhere('phone_hash', $phoneHash)
                ->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Пользователь с таким email или телефоном уже существует.'],
            ]);
        }

        $user = User::create([
            'fullname'   => $data['fullname'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
            'password'   => Hash::make($data['password']),
            'role'       => 'user', // Новые пользователи всегда 'user'
            'email_hash' => $emailHash,
            'phone_hash' => $phoneHash,
        ]);
        
        // Создаем токен для нового пользователя
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->fullUserResponse($user),
        ], 201); // 201 Created
    }

    // POST /api/auth/login
    public function login(Request $request)
    {
        $data = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim($data['login']);
        $hash = hash('sha256', $login);

        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? User::where('email_hash', $hash)->first()
            : User::where('phone_hash', $hash)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Неправильный логин или пароль.'],
            ]);
        }

        // Бан-проверка после успешной верификации credentials. Возвращаем
        // 403 с причиной — юзер должен понимать почему его не пускают.
        // (Замороженных пускаем, у них блокируется только создание контента.)
        if ($user->isBanned()) {
            return response()->json([
                'message'    => 'Аккаунт заблокирован администрацией.',
                'reason'     => $user->ban_reason ?? 'Причина не указана.',
                'banned_at'  => $user->banned_at?->toIso8601String(),
                'error_code' => 'account_banned',
            ], 403);
        }

        // Создаем токен для входа
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->sendLoginNotification($user, $request, 'password');

        return response()->json([
            'token' => $token,
            'user'  => $this->fullUserResponse($user),
        ]);
    }

    // POST /api/auth/passwordless
    public function sendLoginCode(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);
        $email = $data['email'];
        $code = random_int(100000, 999999);
        Cache::put('login_code:' . $email, $code, now()->addMinutes(10));
        Log::info("╔══ КОД ВХОДА ══╗ email={$email}  код={$code}  (действует 10 мин) ╚═══════════════╝");
        try {
            Mail::to($email)->send(new LoginCodeMail($code));
        } catch (\Throwable $e) {
            Log::error('Login code mail failed: ' . $e->getMessage());
            return response()->json(['message' => 'Не удалось отправить письмо. Проверьте корректность email или попробуйте позже.'], 500);
        }
        $response = ['message' => 'Код отправлен на ваш email.'];
        if (config('app.debug')) $response['debug_code'] = $code;
        return response()->json($response);
    }

    // POST /api/auth/passwordless/login
    public function loginWithCode(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string',
        ]);

        $email = $data['email'];
        $code = $data['code'];

        $cachedCode = Cache::get('login_code:' . $email);

        if (! $cachedCode || $cachedCode != $code) {
            throw ValidationException::withMessages([
                'code' => ['Неверный код.'],
            ]);
        }

        Cache::forget('login_code:' . $email);

        // --- ИСПРАВЛЕНИЕ ЗДЕСЬ ---
        // Ищем пользователя по хешу почты, а не по самой почте,
        // так как поле email в базе данных зашифровано.
        $emailHash = hash('sha256', $email);

        $user = User::firstOrCreate(
            ['email_hash' => $emailHash], // Искать нужно по этому уникальному полю
            [
                'email'      => $email, // Это поле попадёт только при создании
                'fullname'   => 'Новый пользователь', // Имя для нового пользователя
                'password'   => Hash::make(Str::random(16)),
                'phone'      => ' ', // Заглушка, т.к. телефона нет
                'phone_hash' => hash('sha256', ' ') // Заглушка для хеша телефона
            ]
        );

        // Бан-проверка для passwordless-входа (тот же гейт что и для пароля)
        if ($user->isBanned()) {
            return response()->json([
                'message'    => 'Аккаунт заблокирован администрацией.',
                'reason'     => $user->ban_reason ?? 'Причина не указана.',
                'banned_at'  => $user->banned_at?->toIso8601String(),
                'error_code' => 'account_banned',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->sendLoginNotification($user, $request, 'code');

        return response()->json([
            'token' => $token,
            'user'  => $this->fullUserResponse($user),
        ]);
    }

    // POST /api/auth/forgot-password
    // Отправляет 6-значный код сброса пароля на почту.
    // Намеренно не раскрывает, существует ли email (безопасность).
    public function sendPasswordResetCode(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);
        $email = $data['email'];

        $emailHash = hash('sha256', $email);
        $user = User::where('email_hash', $emailHash)->first();

        $debugCode = null;
        if ($user) {
            $code = random_int(100000, 999999);
            Cache::put('password_reset:' . $email, $code, now()->addMinutes(15));
            Log::info("╔══ КОД СБРОСА ПАРОЛЯ ══╗ email={$email}  код={$code}  (действует 15 мин) ╚═══════════════════╝");
            try {
                Mail::to($email)->send(new PasswordResetMail($code, $user->fullname));
            } catch (\Throwable $e) {
                Log::error('Password reset mail failed: ' . $e->getMessage());
            }
            if (config('app.debug')) $debugCode = $code;
        }

        // Возвращаем одинаковый ответ независимо от того, найден пользователь или нет
        $response = ['message' => 'Если такой email зарегистрирован, мы отправим на него код сброса пароля.'];
        if ($debugCode !== null) $response['debug_code'] = $debugCode;
        return response()->json($response);
    }

    // POST /api/auth/reset-password
    // Принимает email + код + новый пароль, сбрасывает пароль.
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email'                 => 'required|email',
            'code'                  => 'required|string',
            'password'              => 'required|string|min:6',
            'password_confirmation' => 'required|string',
        ]);

        if ($data['password'] !== $data['password_confirmation']) {
            throw ValidationException::withMessages([
                'password' => ['Пароли не совпадают.'],
            ]);
        }

        $email      = $data['email'];
        $cachedCode = Cache::get('password_reset:' . $email);

        if (! $cachedCode || (string)$cachedCode !== (string)$data['code']) {
            throw ValidationException::withMessages([
                'code' => ['Неверный или устаревший код. Запросите новый.'],
            ]);
        }

        $emailHash = hash('sha256', $email);
        $user = User::where('email_hash', $emailHash)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Пользователь не найден.'],
            ]);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        Cache::forget('password_reset:' . $email);

        return response()->json([
            'message' => 'Пароль успешно изменён. Войдите с новым паролем.',
        ]);
    }

    // POST /api/auth/logout
    public function logout(Request $request)
    {
        // Отзываем текущий токен пользователя
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Вы вышли из системы']);
    }

    // GET /api/auth/me
    public function me(Request $request)
    {
        return response()->json($this->fullUserResponse($request->user()));
    }

    // GET /api/auth/my-reviews
    public function myReviews(Request $request)
    {
        $reviews = $request->user()->reviews()
            ->with('game')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reviews);
    }
    
    // PUT /api/auth/profile
    // Обновляет имя, телефон и аватар. Email меняется отдельно через двухшаговый флоу.
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'fullname'             => 'sometimes|required|string|max:255',
            // Username — публичный, для роута /u/:username (Phase 2).
            // 3-20 символов, lowercase a-z, 0-9, _, точка. Не начинается
            // с цифры или точки. Не оканчивается точкой.
            'username'             => [
                'sometimes', 'nullable', 'string',
                'min:3', 'max:20',
                'regex:/^[a-z][a-z0-9_.]{1,18}[a-z0-9_]$/',
            ],
            'phone'                => 'sometimes|nullable|regex:/^7[0-9]{10}$/',
            'avatar'               => 'sometimes|nullable|string|max:150',
            'notify_login'           => 'sometimes|boolean',
            'notify_order_created'   => 'sometimes|boolean',
            'notify_order_status'    => 'sometimes|boolean',
            'notify_email_comment'   => 'sometimes|boolean',
            'notify_email_reply'     => 'sometimes|boolean',
            'notify_email_reaction'  => 'sometimes|boolean',
            'notify_email_follower'  => 'sometimes|boolean',
            'library_public'         => 'sometimes|boolean',
        ], [
            'username.regex' => 'Username: 3-20 символов, латиница, цифры, _ и точка. Должен начинаться с буквы.',
        ]);

        if (array_key_exists('username', $data)) {
            $username = $data['username'] ? mb_strtolower(trim($data['username'])) : null;

            // Зарезервированные слова — нельзя занимать
            $reserved = [
                'admin','root','support','system','api','help','about','login',
                'register','logout','profile','settings','user','users','u',
                'post','posts','feed','community','soviet','catalog','news',
                'cart','order','orders','review','reviews','mod','mods',
                'gamestore','staff','moderator','manager','official',
            ];
            if ($username && in_array($username, $reserved, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'username' => ['Этот username зарезервирован системой.'],
                ]);
            }

            // Уникальность (case-insensitive — поскольку всегда lowercase)
            if ($username && User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'username' => ['Этот username уже занят.'],
                ]);
            }

            $user->username = $username;
        }

        if (!empty($data['phone'])) {
            $phoneHash = hash('sha256', $data['phone']);
            if (User::where('phone_hash', $phoneHash)->where('id', '!=', $user->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'phone' => ['Этот номер телефона уже используется другим аккаунтом.'],
                ]);
            }
            $user->phone      = $data['phone'];
            $user->phone_hash = $phoneHash;
        }

        if (isset($data['fullname']) && $data['fullname'] !== '') {
            $user->fullname = $data['fullname'];
        }

        if (array_key_exists('avatar', $data)) {
            $user->avatar = $data['avatar'];
        }

        foreach ([
            'notify_login', 'notify_order_created', 'notify_order_status',
            'notify_email_comment', 'notify_email_reply',
            'notify_email_reaction', 'notify_email_follower',
            'library_public',
        ] as $pref) {
            if (array_key_exists($pref, $data)) {
                $user->$pref = (bool) $data[$pref];
            }
        }

        $user->save();

        return response()->json([
            'message' => 'Профиль обновлён',
            'user'    => $this->fullUserResponse($user),
        ]);
    }

    // POST /api/auth/email-change/request
    // Отправляет код подтверждения на НОВЫЙ email. Не меняет email сразу.
    public function requestEmailChange(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $newEmail  = $data['email'];
        $emailHash = hash('sha256', $newEmail);

        if (User::where('email_hash', $emailHash)->where('id', '!=', $user->id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['Этот email уже используется другим аккаунтом.'],
            ]);
        }

        if (strtolower($newEmail) === strtolower($user->email)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['Новый email совпадает с текущим.'],
            ]);
        }

        $code = random_int(100000, 999999);
        Cache::put('email_change:' . $user->id, ['code' => $code, 'new_email' => $newEmail], now()->addMinutes(15));
        Log::info("╔══ КОД СМЕНЫ EMAIL ══╗ userId={$user->id}  новый_email={$newEmail}  код={$code}  (действует 15 мин) ╚══════════════════╝");

        try {
            Mail::to($newEmail)->send(new EmailChangeMail($code, $user->fullname, $newEmail));
        } catch (\Throwable $e) {
            Log::error('Email change mail failed: ' . $e->getMessage());
            return response()->json(['message' => 'Не удалось отправить письмо на указанный email. Проверьте адрес и попробуйте снова.'], 500);
        }

        return response()->json([
            'message' => 'Код подтверждения отправлен на новый email.',
        ]);
    }

    // POST /api/auth/email-change/confirm
    // Проверяет код и обновляет email.
    public function confirmEmailChange(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'code' => 'required|string',
        ]);

        $cached = Cache::get('email_change:' . $user->id);

        if (! $cached || (string)$cached['code'] !== (string)$data['code']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => ['Неверный или устаревший код. Запросите новый.'],
            ]);
        }

        $newEmail  = $cached['new_email'];
        $emailHash = hash('sha256', $newEmail);

        // Повторная проверка на занятость (на случай гонки)
        if (User::where('email_hash', $emailHash)->where('id', '!=', $user->id)->exists()) {
            Cache::forget('email_change:' . $user->id);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['Этот email уже используется другим аккаунтом.'],
            ]);
        }

        $user->email      = $newEmail;
        $user->email_hash = $emailHash;
        $user->save();

        Cache::forget('email_change:' . $user->id);

        return response()->json([
            'message' => 'Email успешно изменён.',
            'user'    => $this->fullUserResponse($user),
        ]);
    }
    
    // POST /api/auth/password
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Текущий пароль неверен'], 422);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json(['message' => 'Пароль изменён']);
    }
}
