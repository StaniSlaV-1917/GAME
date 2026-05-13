@extends('emails.layout', ['title' => 'Ключи к играм'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#fff6df;letter-spacing:0.5px;">
        🗝 Ваши ключи готовы
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        Здравствуйте, <strong style="color:#fff6df;">{{ $userName ?: 'воин' }}</strong>!
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        Оплата по заказу <strong style="color:#ffc979;">#{{ $orderId }}</strong> подтверждена. Ниже — ваши ключи активации.
    </p>

    <!-- Ключи -->
    @foreach($issuedKeys as $item)
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:14px;">
        <tr>
            <td bgcolor="#1a0e08" style="background-color:#1a0e08;padding:18px 20px;border:1px solid #c79a5e;border-radius:6px;">
                <p style="margin:0 0 6px;font-size:12px;letter-spacing:2px;color:#c79a5e;text-transform:uppercase;font-weight:bold;">
                    🎮 {{ $item['title'] }}
                </p>
                <p style="margin:0;font-family:monospace,monospace;font-size:20px;font-weight:bold;color:#ffc979;letter-spacing:3px;word-break:break-all;">
                    {{ $item['key'] }}
                </p>
            </td>
        </tr>
    </table>
    @endforeach

    @if(!empty($missingGames))
    <!-- Игры без ключей -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:8px;">
        <tr>
            <td bgcolor="#2a0e0a" style="background-color:#2a0e0a;padding:16px 20px;border:1px solid #ef4a18;border-radius:6px;">
                <p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;color:#ef4a18;text-transform:uppercase;font-weight:bold;">
                    ⚠ Ключи временно недоступны
                </p>
                <p style="margin:0 0 8px;font-size:13px;color:#d8c49a;">
                    По следующим играм ключи сейчас пополняются. Мы отправим их отдельным письмом в ближайшее время:
                </p>
                @foreach($missingGames as $title)
                <p style="margin:4px 0;font-size:14px;color:#ffc979;">• {{ $title }}</p>
                @endforeach
            </td>
        </tr>
    </table>
    @endif

    <p style="margin:24px 0 0;font-size:13px;color:#9a8672;">
        Просмотр заказов и ключей в личном кабинете:
        <a href="https://game-45428688-fe94e.web.app/profile" style="color:#ff8433;text-decoration:underline;">Профиль → Заказы</a>
    </p>
@endsection
