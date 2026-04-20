<!DOCTYPE html>
<html lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Новый вход — GameStore</title>
  <style>
    @media only screen and (max-width:600px){
      .email-card{width:100%!important;border-radius:0!important;}
      .email-body{padding:30px 20px!important;}
      .info-row td{display:block!important;width:100%!important;padding:8px 0!important;}
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#030712;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#030712;min-height:100vh;">
  <tr>
    <td align="center" style="padding:40px 16px;">

      <table class="email-card" width="600" border="0" cellspacing="0" cellpadding="0" style="background-color:#0d1117;border-radius:20px;overflow:hidden;border:1px solid #1a2a1a;">

        <!-- ═══ HEADER ═══ -->
        <tr>
          <td style="background:linear-gradient(135deg,#050f1a 0%,#0a1f1a 30%,#0c2214 65%,#060e0a 100%);padding:0;">
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
                      <td style="background:linear-gradient(135deg,#15803d,#0891b2);border-radius:16px;padding:2px;">
                        <table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td style="background:linear-gradient(135deg,#05130e,#0c1f1a);border-radius:14px;padding:12px 28px;">
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
                  <span style="font-size:36px;">🛡️</span><br>
                  <span style="font-size:13px;color:#94a3b8;letter-spacing:4px;text-transform:uppercase;font-weight:600;">Уведомление безопасности</span>
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
          <td style="padding:0;line-height:3px;background:linear-gradient(90deg,transparent,#15803d,#0891b2,#15803d,transparent);">&nbsp;</td>
        </tr>

        <!-- ═══ BODY ═══ -->
        <tr>
          <td class="email-body" style="padding:44px 40px;">

            <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#e2e8f0;text-align:center;">
              Привет, {{ $userName }}! 👋
            </p>
            <p style="margin:0 0 36px;font-size:15px;line-height:1.7;color:#64748b;text-align:center;">
              Только что был выполнен вход в твой аккаунт GameStore.<br>
              Если это был ты — всё в порядке.
            </p>

            <!-- SUCCESS BANNER -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
              <tr>
                <td style="background:linear-gradient(135deg,#15803d,#0891b2);border-radius:16px;padding:2px;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td style="background:linear-gradient(135deg,#052010,#0c1f20);border-radius:14px;padding:22px 28px;text-align:center;">
                        <p style="margin:0 0 6px;font-size:32px;">✅</p>
                        <p style="margin:0 0 4px;font-size:20px;font-weight:900;color:#4ade80;">Вход выполнен</p>
                        <p style="margin:0;font-size:13px;color:#475569;">{{ $loginTime }}</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- DEVICE INFO CARDS -->
            <p style="margin:0 0 14px;font-size:12px;font-weight:700;color:#475569;letter-spacing:3px;text-transform:uppercase;">
              Детали сессии
            </p>

            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">

              <!-- IP row -->
              <tr>
                <td style="padding:0 0 10px 0;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background:#0d1f0d;border:1px solid #14532d;border-radius:12px;">
                    <tr>
                      <td width="56" style="padding:16px 0 16px 18px;vertical-align:middle;font-size:24px;">🌐</td>
                      <td style="padding:16px 16px 16px 0;vertical-align:middle;">
                        <p style="margin:0 0 2px;font-size:11px;font-weight:700;color:#475569;letter-spacing:2px;text-transform:uppercase;">IP-адрес</p>
                        <p style="margin:0;font-size:15px;font-weight:700;color:#86efac;font-family:monospace;">{{ $ip }}</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>

              <!-- Browser row -->
              <tr>
                <td style="padding:0 0 10px 0;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background:#0d1a25;border:1px solid #1e3a5f;border-radius:12px;">
                    <tr>
                      <td width="56" style="padding:16px 0 16px 18px;vertical-align:middle;font-size:24px;">🌍</td>
                      <td style="padding:16px 16px 16px 0;vertical-align:middle;">
                        <p style="margin:0 0 2px;font-size:11px;font-weight:700;color:#475569;letter-spacing:2px;text-transform:uppercase;">Браузер</p>
                        <p style="margin:0;font-size:15px;font-weight:700;color:#93c5fd;">{{ $browser }}</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>

              <!-- OS row -->
              <tr>
                <td style="padding:0 0 10px 0;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background:#1a0d25;border:1px solid #4c1d95;border-radius:12px;">
                    <tr>
                      <td width="56" style="padding:16px 0 16px 18px;vertical-align:middle;font-size:24px;">💻</td>
                      <td style="padding:16px 16px 16px 0;vertical-align:middle;">
                        <p style="margin:0 0 2px;font-size:11px;font-weight:700;color:#475569;letter-spacing:2px;text-transform:uppercase;">Операционная система</p>
                        <p style="margin:0;font-size:15px;font-weight:700;color:#c4b5fd;">{{ $os }}</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>

              <!-- Method row -->
              <tr>
                <td>
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background:#1a1205;border:1px solid #78350f;border-radius:12px;">
                    <tr>
                      <td width="56" style="padding:16px 0 16px 18px;vertical-align:middle;font-size:24px;">{{ $loginMethod === 'code' ? '📧' : '🔑' }}</td>
                      <td style="padding:16px 16px 16px 0;vertical-align:middle;">
                        <p style="margin:0 0 2px;font-size:11px;font-weight:700;color:#475569;letter-spacing:2px;text-transform:uppercase;">Способ входа</p>
                        <p style="margin:0;font-size:15px;font-weight:700;color:#fcd34d;">{{ $loginMethod === 'code' ? 'Код из письма' : 'Пароль' }}</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>

            </table>

            <!-- WARNING BLOCK -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;">
              <tr>
                <td style="background:#1c0505;border:1px solid #7f1d1d;border-radius:12px;padding:18px 20px;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="32" valign="top" style="padding-right:12px;font-size:20px;padding-top:2px;">⚠️</td>
                      <td style="font-size:13px;color:#fca5a5;line-height:1.7;">
                        <strong style="color:#f87171;">Это не ты?</strong><br>
                        Немедленно смени пароль в настройках аккаунта. Если доступ утерян — воспользуйся восстановлением через email.
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <p style="margin:0;font-size:13px;color:#334155;text-align:center;line-height:1.6;">
              Управляй уведомлениями в разделе<br>
              <strong style="color:#3b82f6;">Настройки → Уведомления</strong> на сайте GameStore.
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
