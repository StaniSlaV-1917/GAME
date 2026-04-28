@extends('emails.layout', ['title' => 'Статус заказа изменён'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#fff6df;letter-spacing:0.5px;">
        🔄 Изменение статуса заказа
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        Здравствуйте, <strong style="color:#fff6df;">{{ $userName ?: 'воин' }}</strong>!
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        Статус вашего заказа <strong style="color:#ffc979;">#{{ $order->id }}</strong> был изменён.
    </p>

    <!-- Новый статус -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="center" bgcolor="#1a0e08" style="background-color:#1a0e08;padding:24px;border:1px solid #c79a5e;border-radius:6px;">
                <p style="margin:0 0 6px;font-size:11px;letter-spacing:3px;color:#c79a5e;text-transform:uppercase;">
                    Новый статус
                </p>
                <p style="margin:0;font-family:Georgia,serif;font-size:24px;font-weight:bold;color:#ffc979;line-height:1.2;">
                    {{ $statusLabel ?? $order->status }}
                </p>
            </td>
        </tr>
    </table>

    <!-- Сумма заказа для контекста -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:18px;">
        <tr>
            <td style="padding:12px 16px;background-color:#0e0a08;border:1px solid #3d3129;border-radius:6px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:13px;color:#9a8672;">Сумма заказа:</td>
                        <td align="right" style="font-size:14px;color:#fff6df;font-weight:bold;">
                            {{ number_format($order->total, 0, '.', ' ') }} ₽
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- CTA -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:24px;">
        <tr>
            <td align="center">
                <a href="https://game-45428688-fe94e.web.app/profile"
                   style="display:inline-block;padding:12px 32px;background-color:#ef4a18;color:#fff6df;font-family:Georgia,serif;font-size:14px;font-weight:bold;text-decoration:none;border-radius:6px;letter-spacing:1px;text-transform:uppercase;">
                    Открыть заказ →
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:18px 0 0;font-size:12px;color:#7a5a36;text-align:center;">
        Эти уведомления можно отключить в настройках профиля → Email-уведомления.
    </p>
@endsection
