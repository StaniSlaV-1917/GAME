<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    // Список пользователей
    public function index()
    {
        $users = User::select('id', 'fullname', 'email', 'phone', 'role', 'reg_date')
            ->orderByDesc('id')
            ->get();

        return response()->json($users);
    }

    // Обновление ФИО/email/телефона (manager + admin)
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'fullname' => 'nullable|string|max:255',
            'email'    => 'nullable|email|max:255',
            'phone'    => 'nullable|string|max:50',
        ]);

        $user = User::findOrFail($id);
        $user->fill($data);
        $user->save();

        return response()->json([
            'message' => 'Пользователь обновлён',
            'user'    => $user,
        ]);
    }

    // Смена роли (только admin, роут под /admin/users/{id}/role)
    public function updateRole(Request $request, int $id)
    {
        $data = $request->validate([
            'role' => 'required|in:user,manager,admin',
        ]);

        $user = User::findOrFail($id);
        $user->role = $data['role'];
        $user->save();

        return response()->json([
            'message' => 'Роль пользователя обновлена',
            'user'    => $user,
        ]);
    }
}
