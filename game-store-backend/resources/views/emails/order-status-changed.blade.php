<!DOCTYPE html>
<html lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Статус заказа изменён — GameStore</title>
  <style>
    @media only screen and (max-width:600px){
      .email-card{width:100%!important;border-radius:0!important;}
      .email-body{padding:30px 20px!important;}
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#030712;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">

@php
  $statusMap = [
    'created'   => ['icon'=>'🕐','label'=>'Создан',    'color'=>'#3b82f6','bg'=>'#0a1628','border'=>'#1e3a5f','grad1'=>'#1d4ed8','grad2'=>'#6366f1'],
    'paid'      => ['icon'=>'💳','label'=>'Оплачен',   'color'=>'#4ade80','bg'=>'#052010','border'=>'#14532d','grad1'=>'#16a34a','grad2'=>'#0891b2'],
    'shipped'   => ['icon'=>'📦','label'=>'Отправлен', 'color'=>'#a78bfa','bg'=>'#130a2e','border'=>'#4c1d95','grad1'=>'#7c3aed','grad2'=>'#a21caf'],
    'completed' => ['icon'=>'🏆','label'=>'Выполнен',  'color'=>'#fbbf24','bg'=>'#1a1005','border'=>'#78350f','grad1'=>'#d97706','grad2'=>'#16a34a'],
    'cancelled' => ['icon'=>'❌','label'=>'Отменён',   'color'=>'#f87171','bg'=>'#1c0505','border'=>'#7f1d1d','grad1'=>'#dc2626','grad2'=>'#9333ea'],
  ];
  $s = $statusMap[$order->status] ?? $statusMap['created'];
@endphp

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#030712;min-height:100vh;">
  <tr>
    <td align="center" style="padding:40px 16px;">

      <table class="email-card" width="600" border="0" cellspacing="0" cellpadding="0" style="background-color:#0d1117;border-radius:20px;overflow:hidden;border:1px solid #1e2d40;">

        <!-- ═══ HEADER ═══ -->
        <tr>
          <td style="background:linear-gradient(135deg,#0f0c29 0%,#1a0e3d 30%,#0f2460 65%,#0a1628 100%);padding:0;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td style="padding:8px 30px 0;font-size:11px;color:rgba(255,255,255,0.1);letter-spacing:8px;text-align:center;">
                  ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦
                </td>
              </tr>
              <tr>
                <td align="center" style="padding:28px 30px 6px;">
                  <table border="0" cellspacing="0" cellpadding="0" align="center">
                    <tr>
                      <td style="background:linear-gradient(135deg,{{ $s['grad1'] }},{{ $s['grad2'] }});border-radius:16px;padding:2px;">
                        <table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td style="background:linear-gradient(135deg,#1e1b4b,#1e3a5f);border-radius:14px;padding:12px 28px;">
                              <span style="font-size:22px;font-weight:900;color:#ffffff;letter-spacing:3px;text-transform:uppercase;">GAME<span style="color:{{ $s['color'] }};">STORE</span></span>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td align="center" style="padding:10px 30px 30px;">
                  <span style="font-size:36px;">{{ $s['icon'] }}</span><br>
                  <span style="font-size:13px;color:#94a3b8;letter-spacing:4px;text-transform:uppercase;font-weight:600;">Обновление заказа</span>
                </td>
              </tr>
              <tr>
                <td style="padding:0 30px 8px;font-size:11px;color:rgba(255,255,255,0.1);letter-spacing:8px;text-align:center;">
                  ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- DIVIDER -->
        <tr>
          <td style="padding:0;line-height:3px;background:linear-gradient(90deg,transparent,{{ $s['grad1'] }},{{ $s['grad2'] }},{{ $s['grad1'] }},transparent);">&nbsp;</td>
        </tr>

        <!-- ═══ BODY ═══ -->
        <tr>
          <td class="email-body" style="padding:44px 40px;">

            @if($userName)
            <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#e2e8f0;text-align:center;">
              Привет, {{ $userName }}! 👋
            </p>
            @else
            <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#e2e8f0;text-align:center;">
              Привет, игрок! 👋
            </p>
            @endif

            <p style="margin:0 0 32px;font-size:15px;line-height:1.7;color:#64748b;text-align:center;">
              Статус твоего заказа <strong style="color:#e2e8f0;">#{{ $order->id }}</strong> был обновлён.
            </p>

            <!-- STATUS BADGE -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
              <tr>
                <td style="background:linear-gradient(135deg,{{ $s['grad1'] }},{{ $s['grad2'] }});border-radius:16px;padding:2px;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td style="background:{{ $s['bg'] }};border-radius:14px;padding:28px 20px;text-align:center;">
                        <p style="margin:0 0 8px;font-size:44px;line-height:1;">{{ $s['icon'] }}</p>
                        <p style="margin:0 0 4px;font-size:28px;font-weight:900;color:{{ $s['color'] }};letter-spacing:1px;">{{ $statusLabel }}</p>
                        <p style="margin:0;font-size:11px;font-weight:700;color:#475569;letter-spacing:4px;text-transform:uppercase;">Текущий статус</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- PROGRESS BAR -->
            @php
              $steps = ['created','paid','shipped','completed'];
              $currentIdx = array_search($order->status, $steps);
            @endphp
            @if($order->status !== 'cancelled')
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
              <tr>
                @foreach($steps as $idx => $step)
                @php $active = ($currentIdx !== false && $idx <= $currentIdx); @endphp
                <td align="center" style="padding:0 4px;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td style="background:{{ $active ? $s['grad1'] : '#1e2d40' }};border-radius:4px;height:4px;font-size:1px;line-height:4px;">&nbsp;</td>
                    </tr>
                    <tr>
                      <td align="center" style="padding-top:6px;font-size:10px;color:{{ $active ? $s['color'] : '#334155' }};font-weight:{{ $active ? '700' : '400' }};">
                        @if($step==='created') Создан
                        @elseif($step==='paid') Оплачен
                        @elseif($step==='shipped') Отправлен
                        @else Выполнен
                        @endif
                      </td>
                    </tr>
                  </table>
                </td>
                @endforeach
              </tr>
            </table>
            @endif

            <!-- ORDER ITEMS -->
            <p style="margin:0 0 14px;font-size:12px;font-weight:700;color:#475569;letter-spacing:3px;text-transform:uppercase;">
              Состав заказа
            </p>

            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:8px;">
              @foreach($order->items as $item)
              <tr>
                <td style="padding:11px 0;border-bottom:1px solid #111827;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td>
                        <table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td style="background:#111827;border-radius:8px;padding:5px 9px;">
                              <span style="font-size:15px;">🎮</span>
                            </td>
                            <td style="padding-left:10px;">
                              <span style="font-size:14px;font-weight:600;color:#e2e8f0;">{{ $item->game->title ?? 'Игра' }}</span>
                            </td>
                          </tr>
                        </table>
                      </td>
                      <td align="right" style="white-space:nowrap;">
                        <span style="font-size:13px;color:#475569;">{{ $item->quantity }} шт.</span>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              @endforeach
            </table>

            <!-- TOTAL -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;">
              <tr>
                <td style="background:#111827;border-radius:12px;padding:14px 20px;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td><span style="font-size:14px;font-weight:700;color:#94a3b8;">Сумма заказа</span></td>
                      <td align="right"><span style="font-size:20px;font-weight:900;color:#e2e8f0;">{{ number_format($order->total, 0, '.', ' ') }} ₽</span></td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <p style="margin:0;font-size:13px;color:#334155;text-align:center;line-height:1.6;">
              Подробности доступны в твоём<br>
              <strong style="color:#3b82f6;">личном кабинете</strong> на сайте GameStore.
            </p>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="padding:0;line-height:1px;background:linear-gradient(90deg,transparent,#1e2d40,transparent);">&nbsp;</td>
        </tr>
        <tr>
          <td align="center" style="padding:24px 40px;background:#080d14;">
            <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#1e3a5f;letter-spacing:2px;text-transform:uppercase;">GAMESTORE</p>
            <p style="margin:0;font-size:12px;color:#1e2d40;">&copy; {{ date('Y') }} GameStore &nbsp;·&nbsp; Все права защищены</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
