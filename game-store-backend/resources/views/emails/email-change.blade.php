<!DOCTYPE html>
<html lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Подтверждение смены email — GameStore</title>
  <style>
    @media only screen and (max-width:600px){
      .email-card{width:100%!important;border-radius:0!important;}
      .email-body{padding:30px 20px!important;}
      .code-digit{font-size:36px!important;letter-spacing:10px!important;}
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#030712;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#030712;min-height:100vh;">
  <tr>
    <td align="center" style="padding:40px 16px;">

      <table class="email-card" width="600" border="0" cellspacing="0" cellpadding="0" style="background-color:#0d1117;border-radius:20px;overflow:hidden;border:1px solid #0c2040;">

        <!-- ═══ HEADER ═══ -->
        <tr>
          <td style="background:linear-gradient(135deg,#050d1f 0%,#0c1a3d 30%,#0e1f4a 65%,#060e20 100%);padding:0;">
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
                      <td style="background:linear-gradient(135deg,#0ea5e9,#6366f1);border-radius:16px;padding:2px;">
                        <table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td style="background:linear-gradient(135deg,#050d1f,#0c1830);border-radius:14px;padding:12px 28px;">
                              <span style="font-size:22px;font-weight:900;color:#ffffff;letter-spacing:3px;text-transform:uppercase;">GAME<span style="color:#38bdf8;">STORE</span></span>
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
                  <span style="font-size:36px;">✉️</span><br>
                  <span style="font-size:13px;color:#94a3b8;letter-spacing:4px;text-transform:uppercase;font-weight:600;">Смена Email</span>
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
          <td style="padding:0;line-height:3px;background:linear-gradient(90deg,transparent,#0ea5e9,#6366f1,#0ea5e9,transparent);">&nbsp;</td>
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

            <p style="margin:0 0 28px;font-size:15px;line-height:1.7;color:#64748b;text-align:center;">
              Поступил запрос на смену email-адреса.<br>
              Новый адрес: <strong style="color:#38bdf8;">{{ $newEmail }}</strong>
            </p>

            <!-- NEW EMAIL BADGE -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
              <tr>
                <td style="background:#0c1a30;border:1px solid #0c2d48;border-radius:12px;padding:16px 20px;text-align:center;">
                  <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#475569;letter-spacing:4px;text-transform:uppercase;">Новый адрес</p>
                  <p style="margin:0;font-size:18px;font-weight:700;color:#38bdf8;">{{ $newEmail }}</p>
                </td>
              </tr>
            </table>

            <p style="margin:0 0 28px;font-size:14px;line-height:1.7;color:#64748b;text-align:center;">
              Введи код ниже чтобы подтвердить смену.<br>
              Он действует&nbsp;<strong style="color:#f59e0b;">15&nbsp;минут</strong>.
            </p>

            <!-- CODE BLOCK -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;">
              <tr>
                <td style="background:linear-gradient(135deg,#0ea5e9,#6366f1);border-radius:16px;padding:2px;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td style="background:linear-gradient(135deg,#050d1f,#0c1830);border-radius:14px;padding:32px 20px;text-align:center;">
                        <p style="margin:0 0 10px;font-size:11px;font-weight:700;color:#475569;letter-spacing:5px;text-transform:uppercase;">
                          Код подтверждения
                        </p>
                        <p class="code-digit" style="margin:0 0 10px;font-size:52px;font-weight:900;color:#ffffff;letter-spacing:16px;line-height:1;">
                          {{ $code }}
                        </p>
                        <p style="margin:0;font-size:12px;color:#475569;">
                          ⏱ &nbsp;Истекает через 15 минут
                        </p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- WARNING -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;">
              <tr>
                <td style="background:#0a0f1a;border:1px solid #1e3a5f;border-radius:12px;padding:16px 20px;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="32" valign="top" style="padding-right:12px;font-size:18px;">🛡️</td>
                      <td style="font-size:13px;color:#94a3b8;line-height:1.6;">
                        Если ты <strong style="color:#e2e8f0;">не запрашивал</strong> смену email — просто проигнорируй это письмо. Твой текущий адрес останется без изменений.
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <p style="margin:0;font-size:13px;color:#334155;text-align:center;line-height:1.6;">
              Это автоматическое письмо от GameStore.<br>Не отвечай на него.
            </p>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="padding:0;line-height:1px;background:linear-gradient(90deg,transparent,#1e2d40,transparent);">&nbsp;</td>
        </tr>
        <tr>
          <td align="center" style="padding:24px 40px;background:#080d14;">
            <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#0c2d48;letter-spacing:2px;text-transform:uppercase;">GAMESTORE</p>
            <p style="margin:0;font-size:12px;color:#1e2d40;">&copy; {{ date('Y') }} GameStore &nbsp;·&nbsp; Все права защищены</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
