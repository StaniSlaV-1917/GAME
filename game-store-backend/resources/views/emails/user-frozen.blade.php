<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аккаунт заморожен</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #2563eb;">❄ Ваш аккаунт заморожен</h1>

    <p>Здравствуйте, {{ $userName }}!</p>

    <p>
        Администрация GameStore временно заморозила ваш аккаунт.
        Вы можете <strong>входить и просматривать сайт</strong>, но
        <strong>не можете создавать посты, комментарии и реакции</strong>.
    </p>

    <div style="background: #eff6ff; border-left: 4px solid #2563eb; padding: 12px 16px; margin: 16px 0;">
        <strong>Причина заморозки:</strong>
        <p style="margin: 8px 0 0;">{{ $reason }}</p>
    </div>

    <p>Дата заморозки: <strong>{{ $frozenAt }}</strong></p>

    <p>
        Если вы считаете что заморозка установлена по ошибке — свяжитесь с поддержкой:
        <a href="mailto:Gamestore.help@yandex.com">Gamestore.help@yandex.com</a>
    </p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">

    <p style="color: #666; font-size: 0.9em;">
        Это автоматическое сообщение от GameStore. Не отвечайте на него.
    </p>
</body>
</html>
