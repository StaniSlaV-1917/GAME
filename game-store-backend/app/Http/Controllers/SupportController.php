<?php

namespace App\Http\Controllers;

use App\Mail\SupportRequestMail;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    /**
     * POST /api/support/send
     * Принимает обращение из чата поддержки и пересылает на почту поддержки.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'user_email'  => 'required|email|max:255',
            'problem_path'=> 'required|string|max:500',
            'message'     => 'required|string|min:5|max:3000',
            'user_name'   => 'nullable|string|max:255',
        ]);

        $supportEmail = config('mail.support_address', config('mail.from.address'));

        // Сначала отправляем письмо — если не получилось, тикет не создаём
        try {
            Mail::to($supportEmail)->send(new SupportRequestMail(
                userEmail:   $data['user_email'],
                problemPath: $data['problem_path'],
                body:        $data['message'],
                userName:    $data['user_name'] ?? null,
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Support mail failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Не удалось отправить обращение. Проверьте подключение к интернету и попробуйте снова.',
            ], 500);
        }

        // Письмо дошло — фиксируем тикет в БД
        $ticket = SupportTicket::create([
            'user_email'   => $data['user_email'],
            'user_name'    => $data['user_name'] ?? null,
            'problem_path' => $data['problem_path'],
            'body'         => $data['message'],
            'status'       => 'new',
        ]);

        return response()->json([
            'message'   => 'Обращение принято. Мы ответим на ' . $data['user_email'],
            'ticket_id' => $ticket->id,
        ]);
    }
}
