<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $title ?? 'GameStore' }}</title>
</head>
{{-- Email-safe HTML: tables-only layout, inline CSS, web-safe fonts.
     Дизайн под Ashenforge: тёмный фон, ember-акценты, кованые рамки,
     сигил Орды как Unicode-символ ⚔ (картинки блокируются в Gmail
     по дефолту, поэтому текст). --}}
<body style="margin:0;padding:0;background-color:#0a0806;font-family:Georgia,'Times New Roman',Times,serif;color:#d8c49a;line-height:1.6;-webkit-text-size-adjust:100%;">

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#0a0806" style="background-color:#0a0806;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <!-- ═══ КАРТОЧКА (max-width 600px) ═══ -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;background-color:#14100c;border:1px solid #3d3129;border-radius:6px;">

                    <!-- ═══ Декоративная ember-линия ═══ -->
                    <tr>
                        <td height="3" bgcolor="#ef4a18" style="background-color:#ef4a18;font-size:0;line-height:0;border-radius:6px 6px 0 0;">&nbsp;</td>
                    </tr>

                    <!-- ═══ ШАПКА (логотип + tagline) ═══ -->
                    <tr>
                        <td align="center" bgcolor="#1a0e08" style="background-color:#1a0e08;padding:32px 24px 24px;border-bottom:1px solid #3d3129;">

                            <!-- Сигил Орды (Unicode-символ ⚔) с ember-glow в обёртке -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center" bgcolor="#2a1a14" style="background-color:#2a1a14;width:64px;height:64px;border-radius:50%;border:2px solid #c79a5e;text-align:center;">
                                        <span style="font-size:28px;color:#ffc979;line-height:60px;display:inline-block;">⚔</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Wordmark -->
                            <p style="margin:18px 0 4px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;letter-spacing:4px;color:#fff6df;text-transform:uppercase;">
                                GAMESTORE
                            </p>
                            <p style="margin:0;font-family:Georgia,serif;font-size:11px;letter-spacing:3px;color:#c79a5e;text-transform:uppercase;">
                                Кузница воина
                            </p>
                        </td>
                    </tr>

                    <!-- ═══ КОНТЕНТ ═══ -->
                    <tr>
                        <td style="padding:32px 36px 28px;color:#d8c49a;font-family:Georgia,'Times New Roman',serif;font-size:15px;line-height:1.7;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- ═══ ФУТЕР ═══ -->
                    <tr>
                        <td bgcolor="#0e0a08" style="background-color:#0e0a08;padding:20px 36px;border-top:1px solid #3d3129;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="font-family:Georgia,serif;font-size:11px;color:#7a5a36;line-height:1.6;">
                                        <p style="margin:0 0 6px;">
                                            © {{ date('Y') }} GameStore. Кузница воина.
                                        </p>
                                        <p style="margin:0 0 4px;">
                                            <a href="https://game-45428688-fe94e.web.app/" style="color:#c79a5e;text-decoration:none;">game-45428688-fe94e.web.app</a>
                                            &nbsp;·&nbsp;
                                            <a href="mailto:Gamestore.help@yandex.com" style="color:#c79a5e;text-decoration:none;">Gamestore.help@yandex.com</a>
                                        </p>
                                        <p style="margin:6px 0 0;color:#5c4a32;font-style:italic;">
                                            Lok-tar ogar!
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <!-- ═══ Дисклеймер вне карточки ═══ -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;margin-top:16px;">
                    <tr>
                        <td align="center" style="padding:0 24px;font-family:Georgia,serif;font-size:11px;color:#5c4a32;line-height:1.5;">
                            Это автоматическое письмо от GameStore. Не отвечайте на него — для связи с поддержкой используйте email выше.
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
