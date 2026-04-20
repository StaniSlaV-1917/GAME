<!DOCTYPE html>
<html lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Заказ оформлен — GameStore</title>
  <style>
    @media only screen and (max-width:600px){
      .email-card{width:100%!important;border-radius:0!important;}
      .email-body{padding:30px 20px!important;}
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#030712;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#030712;min-height:100vh;">
  <tr>
    <td align="center" style="padding:40px 16px;">

      <table class="email-card" width="600" border="0" cellspacing="0" cellpadding="0" style="background-color:#0d1117;border-radius:20px;overflow:hidden;border:1px solid #0d2d1a;">

        <!-- ═══ HEADER ═══ -->
        <tr>
          <td style="background:linear-gradient(135deg,#052010 0%,#0a3320 30%,#0d1f3c 65%,#060e1a 100%);padding:0;">
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
                      <td style="background:linear-gradient(135deg,#16a34a,#0891b2);border-radius:16px;padding:2px;">
                        <table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td style="background:linear-gradient(135deg,#052010,#0c1f30);border-radius:14px;padding:12px 28px;">
                              <span style="font-size:22px;font-weight:900;color:#ffffff;letter-spacing:3px;text-transform:uppercase;">GAME<span style="color:#4ade80;">STORE</span></span>
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
                  <span style="font-size:36px;">🎮</span><br>
                  <span style="font-size:13px;color:#94a3b8;letter-spacing:4px;text-transform:uppercase;font-weight:600;">Заказ оформлен</span>
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
          <td style="padding:0;line-height:3px;background:linear-gradient(90deg,transparent,#16a34a,#0891b2,#16a34a,transparent);">&nbsp;</td>
        </tr>

        <!-- ═══ BODY ═══ -->
        <tr>
          <td class="email-body" style="padding:44px 40px;">

            @if($userName)
            <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#e2e8f0;text-align:center;">
              Поздравляем, {{ $userName }}! 🎉
            </p>
            @else
            <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#e2e8f0;text-align:center;">
              Поздравляем! 🎉
            </p>
            @endif

            <p style="margin:0 0 32px;font-size:15px;line-height:1.7;color:#64748b;text-align:center;">
              Твой заказ успешно оформлен.<br>Спасибо за покупку в GameStore!
            </p>

            <!-- ORDER BADGE -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
              <tr>
                <td style="background:linear-gradient(135deg,#16a34a,#0891b2);border-radius:16px;padding:2px;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td style="background:linear-gradient(135deg,#052010,#0c1f30);border-radius:14px;padding:22px 28px;text-align:center;">
                        <p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#475569;letter-spacing:4px;text-transform:uppercase;">Номер заказа</p>
                        <p style="margin:0;font-size:38px;font-weight:900;color:#4ade80;letter-spacing:4px;line-height:1.1;">#{{ $order->id }}</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- SECTION TITLE -->
            <p style="margin:0 0 14px;font-size:12px;font-weight:700;color:#475569;letter-spacing:3px;text-transform:uppercase;">
              Состав заказа
            </p>

            <!-- ORDER ITEMS -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:8px;">
              @foreach($order->items as $item)
              <tr>
                <td style="padding:12px 0;border-bottom:1px solid #111827;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td style="vertical-align:middle;">
                        <table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td style="background:#111827;border-radius:8px;padding:6px 10px;margin-right:12px;">
                              <span style="font-size:16px;">🎮</span>
                            </td>
                            <td style="padding-left:12px;">
                              <span style="font-size:15px;font-weight:600;color:#e2e8f0;">{{ $item->game->title ?? 'Игра' }}</span>
                            </td>
                          </tr>
                        </table>
                      </td>
                      <td align="right" style="white-space:nowrap;vertical-align:middle;">
                        <span style="font-size:13px;color:#475569;margin-right:8px;">{{ $item->quantity }} шт.</span>
                        <span style="font-size:15px;font-weight:700;color:#4ade80;">{{ number_format($item->price, 0, '.', ' ') }} ₽</span>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              @endforeach
            </table>

            <!-- TOTAL ROW -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;">
              <tr>
                <td style="background:#111827;border-radius:12px;padding:16px 20px;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td><span style="font-size:15px;font-weight:700;color:#94a3b8;">Итого к оплате</span></td>
                      <td align="right"><span style="font-size:22px;font-weight:900;color:#4ade80;">{{ number_format($order->total, 0, '.', ' ') }} ₽</span></td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- STATUS INFO -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;">
              <tr>
                <td style="background:#0a1f10;border:1px solid #14532d;border-radius:12px;padding:18px 20px;text-align:center;">
                  <p style="margin:0 0 6px;font-size:13px;color:#4ade80;font-weight:600;">🕐 &nbsp;Статус: Создан</p>
                  <p style="margin:0;font-size:13px;color:#475569;">Мы уведомим тебя о каждом изменении статуса заказа</p>
                </td>
              </tr>
            </table>

            <p style="margin:0;font-size:13px;color:#334155;text-align:center;line-height:1.6;">
              Подробности заказа доступны в твоём<br>
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
            <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#14532d;letter-spacing:2px;text-transform:uppercase;">GAMESTORE</p>
            <p style="margin:0;font-size:12px;color:#1e2d40;">&copy; {{ date('Y') }} GameStore &nbsp;·&nbsp; Все права защищены</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
