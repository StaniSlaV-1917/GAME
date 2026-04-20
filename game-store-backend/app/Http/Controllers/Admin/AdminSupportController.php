<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class AdminSupportController extends Controller
{
    // GET /api/admin/support
    public function index(Request $request)
    {
        $query = SupportTicket::orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('user_email', 'like', "%{$q}%")
                  ->orWhere('user_name',    'like', "%{$q}%")
                  ->orWhere('problem_path', 'like', "%{$q}%")
                  ->orWhere('body',         'like', "%{$q}%");
            });
        }

        return response()->json($query->get());
    }

    // PUT /api/admin/support/{id}
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'status'     => 'required|in:new,in_progress,resolved,rejected',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $ticket->status     = $data['status'];
        $ticket->admin_note = $data['admin_note'] ?? $ticket->admin_note;
        $ticket->save();

        return response()->json([
            'message' => 'Статус обращения обновлён.',
            'ticket'  => $ticket,
        ]);
    }

    // DELETE /api/admin/support/{id}
    public function destroy(int $id)
    {
        SupportTicket::findOrFail($id)->delete();
        return response()->json(['message' => 'Обращение удалено.']);
    }
}
