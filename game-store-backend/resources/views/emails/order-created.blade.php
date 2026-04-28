@extends('emails.layout', ['title' => 'Заказ оформлен'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#fff6df;letter-spacing:0.5px;">
        ⚔ Заказ принят в кузницу
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        Здравствуйте, <strong style="color:#fff6df;">{{ $userName ?: 'воин' }}</strong>!
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        Ваш заказ оформлен. Ключи будут отправлены на ваш email после подтверждения оплаты.
    </p>

    <!-- Номер заказа -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="center" bgcolor="#1a0e08" style="background-color:#1a0e08;padding:20px;border:1px solid #c79a5e;border-radius:6px;">
                <p style="margin:0 0 4px;font-size:11px;letter-spacing:3px;color:#c79a5e;text-transform:uppercase;">
                    Номер заказа
                </p>
                <p style="margin:0;font-family:Georgia,serif;font-size:32px;font-weight:bold;color:#ffc979;line-height:1.2;">
                    #{{ $order->id }}
                </p>
            </td>
        </tr>
    </table>

    <!-- Состав заказа -->
    <p style="margin:24px 0 12px;font-size:11px;letter-spacing:3px;color:#c79a5e;text-transform:uppercase;font-weight:bold;">
        Состав заказа
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#1a0e08" style="background-color:#1a0e08;border:1px solid #3d3129;border-radius:6px;">
        @foreach($order->items as $item)
        <tr>
            <td style="padding:14px 18px;{{ !$loop->last ? 'border-bottom:1px solid #3d3129;' : '' }}">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:14px;color:#fff6df;font-weight:bold;">
                            🎮 {{ $item->game->title ?? 'Игра' }}
                        </td>
                        <td align="right" style="white-space:nowrap;">
                            <span style="font-size:12px;color:#9a8672;margin-right:10px;">{{ $item->quantity }} шт.</span>
                            <span style="font-size:14px;font-weight:bold;color:#ffc979;">{{ number_format($item->price, 0, '.', ' ') }} ₽</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @endforeach
    </table>

    <!-- Итог -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:14px;">
        <tr>
            <td bgcolor="#2a0e0a" style="background-color:#2a0e0a;padding:16px 20px;border:1px solid #ef4a18;border-radius:6px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:14px;color:#d8c49a;text-transform:uppercase;letter-spacing:1px;">Итого к оплате:</td>
                        <td align="right">
                            <span style="font-family:Georgia,serif;font-size:24px;font-weight:bold;color:#ffc979;">
                                {{ number_format($order->total, 0, '.', ' ') }} ₽
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0;font-size:13px;color:#9a8672;">
        Отслеживайте статус заказа в личном кабинете:
        <a href="https://game-45428688-fe94e.web.app/profile" style="color:#ff8433;text-decoration:underline;">Профиль → Заказы</a>
    </p>
@endsection
