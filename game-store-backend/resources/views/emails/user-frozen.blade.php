@extends('emails.layout', ['title' => 'Аккаунт заморожен'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#7db3d4;letter-spacing:0.5px;">
        ❄ Ваш аккаунт заморожен
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        Здравствуйте, <strong style="color:#fff6df;">{{ $userName }}</strong>!
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        Совет старейшин временно <strong style="color:#7db3d4;">заморозил</strong> ваш аккаунт. Вы можете
        <strong style="color:#fff6df;">входить и просматривать</strong> сайт, но
        <strong style="color:#ff8433;">не можете создавать посты, комментарии и реакции</strong>.
    </p>

    <!-- Причина -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td bgcolor="#0a1828" style="background-color:#0a1828;padding:18px 22px;border:1px solid #4a7395;border-left:4px solid #7db3d4;border-radius:6px;">
                <p style="margin:0 0 8px;font-size:11px;letter-spacing:3px;color:#7db3d4;text-transform:uppercase;font-weight:bold;">
                    Причина заморозки
                </p>
                <p style="margin:0;font-size:14px;color:#fff6df;line-height:1.6;">{{ $reason }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:18px 0 0;font-size:13px;color:#9a8672;">
        <strong style="color:#c79a5e;">Дата заморозки:</strong> {{ $frozenAt }}
    </p>

    <!-- Что можно / нельзя -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:20px;">
        <tr>
            <td bgcolor="#0e0a08" style="background-color:#0e0a08;padding:16px 20px;border:1px solid #3d3129;border-radius:6px;">
                <p style="margin:0 0 10px;font-size:13px;color:#a3c755;">
                    <strong>✓ Доступно:</strong> вход, чтение, покупки, заказы, профиль
                </p>
                <p style="margin:0;font-size:13px;color:#ff8433;">
                    <strong>✗ Недоступно:</strong> новые посты, комментарии, реакции
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:18px 0 0;font-size:13px;color:#9a8672;line-height:1.6;">
        Если вы считаете заморозку несправедливой — свяжитесь с поддержкой:
        <a href="mailto:Gamestore.help@yandex.com" style="color:#ff8433;text-decoration:underline;">Gamestore.help@yandex.com</a>
    </p>
@endsection
