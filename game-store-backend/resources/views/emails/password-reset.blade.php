@extends('emails.layout', ['title' => 'Сброс пароля'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#fff6df;letter-spacing:0.5px;">
        🔨 Перековка ключа
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        @if($userName)
            Здравствуйте, <strong style="color:#fff6df;">{{ $userName }}</strong>!
        @else
            Здравствуйте, воин!
        @endif
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        Поступил запрос на перековку (сброс) пароля для вашего аккаунта. Если это были вы — введите код ниже на странице сброса пароля.
    </p>

    <!-- Код -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="center" bgcolor="#1a0e08" style="background-color:#1a0e08;padding:28px 24px;border:1px solid #c79a5e;border-radius:6px;">
                <p style="margin:0 0 6px;font-size:11px;letter-spacing:3px;color:#c79a5e;text-transform:uppercase;">
                    Код для перековки
                </p>
                <p style="margin:0;font-family:'Courier New',Courier,monospace;font-size:38px;font-weight:bold;letter-spacing:10px;color:#ffc979;line-height:1.2;">
                    {{ $code }}
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0;font-size:13px;color:#9a8672;">
        Код действителен <strong style="color:#c79a5e;">15 минут</strong>. После ввода вы сможете задать новый пароль.
    </p>

    <!-- Warning блок -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:18px;">
        <tr>
            <td bgcolor="#2a0a08" style="background-color:#2a0a08;padding:14px 16px;border-left:3px solid #ef4a18;border-radius:3px;">
                <p style="margin:0;font-size:13px;color:#ffc979;line-height:1.5;">
                    <strong>⚠ Не запрашивали сброс?</strong><br>
                    Возможно, кто-то пытается получить доступ к вашему аккаунту. Не сообщайте этот код никому и при необходимости свяжитесь с поддержкой.
                </p>
            </td>
        </tr>
    </table>
@endsection
