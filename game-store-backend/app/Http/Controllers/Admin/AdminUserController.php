<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'fullname', 'email', 'phone', 'role', 'reg_date')
            ->orderByDesc('id')
            ->get();

        return response()->json($users);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'fullname' => 'nullable|string|max:255',
            'email'    => 'nullable|email|max:255',
            'phone'    => 'nullable|string|max:50',
        ]);

        $user = User::findOrFail($id);
        if (array_key_exists('fullname', $data)) $user->fullname = $data['fullname'];
        if (array_key_exists('email', $data))    $user->email    = $data['email'];
        if (array_key_exists('phone', $data))    $user->phone    = $data['phone'];
        $user->save();

        return response()->json([
            'message' => 'Пользователь обновлён',
            'user'    => $user->only(['id', 'fullname', 'email', 'phone', 'role', 'reg_date']),
        ]);
    }

    public function updateRole(Request $request, int $id)
    {
        $data = $request->validate([
            'role' => 'required|in:user,manager,admin',
        ]);

        DB::table('users')->where('id', $id)->update(['role' => $data['role']]);

        $user = User::findOrFail($id);

        return response()->json([
            'message' => 'Роль пользователя обновлена',
            'user'    => $user->only(['id', 'fullname', 'email', 'phone', 'role', 'reg_date']),
        ]);
    }
}
