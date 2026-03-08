<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Формирует стандартизированный ответ с данными пользователя.
     */
    private function fullUserResponse(User $user)
{
    return [
        'id'       => $user->id,
        'fullname' => $user->fullname,
        'email'    => $user->email,
        'phone'    => $user->phone,
        'is_admin' => $user->role === 'admin',
        'reg_date' => $user->reg_date, // ← вместо created_at
    ];
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

        // Создаем токен для входа
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->fullUserResponse($user),
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
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'fullname' => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|email',
            'phone'    => 'sometimes|required|regex:/^7[0-9]{10}$/',
        ]);

        if (!empty($data['email'])) {
            $emailHash = hash('sha256', $data['email']);
            if (User::where('email_hash', $emailHash)->where('id', '!=', $user->id)->exists()) {
                return response()->json(['message' => 'Email уже занят'], 422);
            }
            $user->email = $data['email'];
            $user->email_hash = $emailHash;
        }

        if (!empty($data['phone'])) {
            $phoneHash = hash('sha256', $data['phone']);
            if (User::where('phone_hash', $phoneHash)->where('id', '!=', $user->id)->exists()) {
                return response()->json(['message' => 'Телефон уже занят'], 422);
            }
            $user->phone = $data['phone'];
            $user->phone_hash = $phoneHash;
        }

        if (!empty($data['fullname'])) {
            $user->fullname = $data['fullname'];
        }

        $user->save();

        return response()->json([
            'message' => 'Профиль обновлён',
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
