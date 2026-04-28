@extends('emails.layout', ['title' => 'Аккаунт заблокирован'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#ef4a18;letter-spacing:0.5px;">
        🚫 Ваш аккаунт заблокирован
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        Здравствуйте, <strong style="color:#fff6df;">{{ $userName }}</strong>!
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        Совет старейшин принял решение наложить блокировку на ваш аккаунт. Вход на сайт временно невозможен.
    </p>

    <!-- Причина -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td bgcolor="#2a0a08" style="background-color:#2a0a08;padding:18px 22px;border:1px solid #ef4a18;border-left:4px solid #ef4a18;border-radius:6px;">
                <p style="margin:0 0 8px;font-size:11px;letter-spacing:3px;color:#ff8433;text-transform:uppercase;font-weight:bold;">
                    Причина блокировки
                </p>
                <p style="margin:0;font-size:14px;color:#fff6df;line-height:1.6;">{{ $reason }}</p>
            </td>
        </tr>
    </table>

    <!-- Дата -->
    <p style="margin:18px 0 0;font-size:13px;color:#9a8672;">
        <strong style="color:#c79a5e;">Дата блокировки:</strong> {{ $bannedAt }}
    </p>

    <!-- Контакты -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:24px;">
        <tr>
            <td bgcolor="#0e0a08" style="background-color:#0e0a08;padding:16px 20px;border:1px solid #3d3129;border-radius:6px;">
                <p style="margin:0;font-size:13px;color:#d8c49a;line-height:1.6;">
                    Если вы считаете что блокировка наложена несправедливо — обратитесь в поддержку:<br>
                    <a href="mailto:Gamestore.help@yandex.com" style="color:#ff8433;text-decoration:underline;">Gamestore.help@yandex.com</a>
                </p>
            </td>
        </tr>
    </table>
@endsection
