<!DOCTYPE html>
<html lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Обращение в поддержку — GameStore</title>
</head>
<body style="margin:0;padding:0;background-color:#030712;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#030712;min-height:100vh;">
  <tr>
    <td align="center" style="padding:40px 16px;">

      <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color:#0d1117;border-radius:20px;overflow:hidden;border:1px solid #1e2d40;">

        <!-- HEADER -->
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
                      <td style="background:linear-gradient(135deg,#f59e0b,#ef4444);border-radius:16px;padding:2px;">
                        <table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td style="background:linear-gradient(135deg,#1c1005,#1f0a0a);border-radius:14px;padding:12px 28px;">
                              <span style="font-size:22px;font-weight:900;color:#ffffff;letter-spacing:3px;text-transform:uppercase;">GAME<span style="color:#fcd34d;">STORE</span></span>
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
                  <span style="font-size:36px;">🎧</span><br>
                  <span style="font-size:13px;color:#94a3b8;letter-spacing:4px;text-transform:uppercase;font-weight:600;">Новое обращение в поддержку</span>
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
          <td style="padding:0;line-height:3px;background:linear-gradient(90deg,transparent,#f59e0b,#ef4444,#f59e0b,transparent);">&nbsp;</td>
        </tr>

        <!-- BODY -->
        <tr>
          <td style="padding:40px 40px;">

            <!-- FROM USER BLOCK -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
              <tr>
                <td style="background:linear-gradient(135deg,#f59e0b,#ef4444);border-radius:16px;padding:2px;">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td style="background:linear-gradient(135deg,#1c1005,#1f0a0a);border-radius:14px;padding:20px 24px;">
                        <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#475569;letter-spacing:4px;text-transform:uppercase;">Отправитель</p>
                        @if($userName)
                        <p style="margin:0 0 4px;font-size:18px;font-weight:700;color:#fcd34d;">{{ $userName }}</p>
                        @endif
                        <p style="margin:0;font-size:15px;color:#fbbf24;font-family:monospace;">📧 {{ $userEmail }}</p>
                        <p style="margin:6px 0 0;font-size:11px;color:#475569;">Ответь на это письмо — ответ уйдёт напрямую пользователю</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- PROBLEM PATH -->
            <p style="margin:0 0 12px;font-size:12px;font-weight:700;color:#475569;letter-spacing:3px;text-transform:uppercase;">
              Путь проблемы
            </p>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
              <tr>
                <td style="background:#0a1628;border:1px solid #1e3a5f;border-radius:12px;padding:16px 20px;">
                  @php $parts = explode(' → ', $problemPath); @endphp
                  <table border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      @foreach($parts as $i => $part)
                      <td style="vertical-align:middle;">
                        @if($i > 0)
                        <span style="font-size:14px;color:#334155;margin:0 6px;">→</span>
                        @endif
                        <span style="display:inline-block;background:{{ $i === count($parts)-1 ? '#1e3a5f' : '#111827' }};border:1px solid {{ $i === count($parts)-1 ? '#3b82f6' : '#1e2d40' }};border-radius:6px;padding:4px 10px;font-size:13px;font-weight:{{ $i === count($parts)-1 ? '700' : '400' }};color:{{ $i === count($parts)-1 ? '#93c5fd' : '#64748b' }};">
                          {{ trim($part) }}
                        </span>
                      </td>
                      @endforeach
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- MESSAGE -->
            <p style="margin:0 0 12px;font-size:12px;font-weight:700;color:#475569;letter-spacing:3px;text-transform:uppercase;">
              Описание проблемы
            </p>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;">
              <tr>
                <td style="background:#111827;border:1px solid #1e2d40;border-radius:12px;padding:20px 24px;">
                  <p style="margin:0;font-size:15px;color:#e2e8f0;line-height:1.8;white-space:pre-wrap;">{{ $body }}</p>
                </td>
              </tr>
            </table>

            <!-- META -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td style="background:#080d14;border-radius:10px;padding:14px 18px;">
                  <p style="margin:0;font-size:12px;color:#334155;line-height:1.6;">
                    🕐 &nbsp;Получено: <strong style="color:#475569;">{{ now()->setTimezone('Europe/Moscow')->format('d.m.Y в H:i') }} МСК</strong><br>
                    🌐 &nbsp;Источник: <strong style="color:#475569;">Чат поддержки GameStore</strong>
                  </p>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="padding:0;line-height:1px;background:linear-gradient(90deg,transparent,#1e2d40,transparent);">&nbsp;</td>
        </tr>
        <tr>
          <td align="center" style="padding:24px 40px;background:#080d14;">
            <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#78350f;letter-spacing:2px;text-transform:uppercase;">GAMESTORE SUPPORT</p>
            <p style="margin:0;font-size:12px;color:#1e2d40;">&copy; {{ date('Y') }} GameStore &nbsp;·&nbsp; Внутреннее письмо поддержки</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
