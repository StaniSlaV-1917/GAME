@extends('emails.layout', ['title' => 'Новый вход в аккаунт'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#fff6df;letter-spacing:0.5px;">
        🔓 Новый вход в ваш аккаунт
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        Здравствуйте, <strong style="color:#fff6df;">{{ $userName ?: 'воин' }}</strong>!
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        В ваш аккаунт только что был выполнен вход. Если это были вы — всё в порядке.
    </p>

    <!-- Информация о входе -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#1a0e08" style="background-color:#1a0e08;border:1px solid #3d3129;border-radius:6px;">
        <tr>
            <td style="padding:18px 22px;">
                <table role="presentation" cellpadding="6" cellspacing="0" border="0" width="100%" style="font-size:14px;color:#d8c49a;">
                    <tr>
                        <td width="35%" style="color:#c79a5e;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Способ:</td>
                        <td style="color:#fff6df;">{{ $loginMethod === 'code' ? 'По коду' : 'По паролю' }}</td>
                    </tr>
                    <tr>
                        <td style="color:#c79a5e;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Время:</td>
                        <td style="color:#fff6df;">{{ $loginTime }}</td>
                    </tr>
                    <tr>
                        <td style="color:#c79a5e;font-size:12px;text-transform:uppercase;letter-spacing:1px;">IP:</td>
                        <td style="color:#fff6df;font-family:'Courier New',monospace;font-size:13px;">{{ $ip }}</td>
                    </tr>
                    <tr>
                        <td style="color:#c79a5e;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Браузер:</td>
                        <td style="color:#fff6df;">{{ $browser }}</td>
                    </tr>
                    <tr>
                        <td style="color:#c79a5e;font-size:12px;text-transform:uppercase;letter-spacing:1px;">ОС:</td>
                        <td style="color:#fff6df;">{{ $os }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Warning -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:20px;">
        <tr>
            <td bgcolor="#2a0a08" style="background-color:#2a0a08;padding:14px 16px;border-left:3px solid #ef4a18;border-radius:3px;">
                <p style="margin:0;font-size:13px;color:#ffc979;line-height:1.5;">
                    <strong>⚠ Это были не вы?</strong><br>
                    Срочно <a href="https://game-45428688-fe94e.web.app/login" style="color:#ff8433;text-decoration:underline;">смените пароль</a> и свяжитесь с поддержкой:
                    <a href="mailto:Gamestore.help@yandex.com" style="color:#ff8433;text-decoration:underline;">Gamestore.help@yandex.com</a>
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:18px 0 0;font-size:12px;color:#7a5a36;">
        Эти уведомления можно отключить в настройках профиля → Email-уведомления.
    </p>
@endsection
