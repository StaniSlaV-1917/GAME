<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ваш код для входа в GameStore</title>
</head>
<body style="margin: 0; padding: 0; background-color: #030712; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; color: #e5e7eb;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background: rgba(17, 24, 39, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; backdrop-filter: blur(12px);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 40px 30px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                            <h1 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff;">
                                GameStore
                            </h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 25px; font-size: 18px; line-height: 1.6; text-align: center;">
                                Здравствуйте!
                            </p>
                            <p style="margin: 0 0 30px; font-size: 16px; line-height: 1.6; text-align: center; color: #9ca3af;">
                                Используйте этот код, чтобы войти в ваш аккаунт. Никому не сообщайте его.
                            </p>
                            <div style="background-color: rgba(31, 41, 55, 0.5); border-radius: 8px; padding: 20px; text-align: center;">
                                <p style="margin: 0 0 10px; font-size: 16px; color: #d1d5db; letter-spacing: 1px; text-transform: uppercase;">Ваш код для входа</p>
                                <p style="margin: 0; font-size: 36px; font-weight: 700; color: #ffffff; letter-spacing: 4px; line-height: 1.2;">
                                    {{ $code }}
                                </p>
                            </div>
                             <p style="margin: 30px 0 0; font-size: 16px; line-height: 1.6; text-align: center; color: #9ca3af;">
                                Если вы не запрашивали этот код, просто проигнорируйте это письмо.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                            <p style="margin: 0; font-size: 14px; color: #6b7280;">
                                &copy; {{ date('Y') }} GameStore. Все права защищены.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
