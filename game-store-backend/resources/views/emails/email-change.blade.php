@extends('emails.layout', ['title' => 'Смена email'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#fff6df;letter-spacing:0.5px;">
        ✉ Подтверждение смены email
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        @if($userName)
            Здравствуйте, <strong style="color:#fff6df;">{{ $userName }}</strong>!
        @else
            Здравствуйте, воин!
        @endif
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        Поступил запрос на смену email на адрес
        @if($newEmail)
            <strong style="color:#ffc979;">{{ $newEmail }}</strong>.
        @else
            этот.
        @endif
        Чтобы подтвердить смену — введите код ниже в настройках профиля.
    </p>

    <!-- Код -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="center" bgcolor="#1a0e08" style="background-color:#1a0e08;padding:28px 24px;border:1px solid #c79a5e;border-radius:6px;">
                <p style="margin:0 0 6px;font-size:11px;letter-spacing:3px;color:#c79a5e;text-transform:uppercase;">
                    Код подтверждения
                </p>
                <p style="margin:0;font-family:'Courier New',Courier,monospace;font-size:38px;font-weight:bold;letter-spacing:10px;color:#ffc979;line-height:1.2;">
                    {{ $code }}
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0;font-size:13px;color:#9a8672;">
        Код действителен <strong style="color:#c79a5e;">10 минут</strong>.
    </p>

    <p style="margin:12px 0 0;font-size:13px;color:#9a8672;">
        Если вы не запрашивали смену email — проигнорируйте это письмо. Ваш текущий email останется без изменений.
    </p>
@endsection
