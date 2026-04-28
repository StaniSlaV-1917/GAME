<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аккаунт заблокирован</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #b91c1c;">🚫 Ваш аккаунт заблокирован</h1>

    <p>Здравствуйте, {{ $userName }}!</p>

    <p>Администрация GameStore приняла решение заблокировать ваш аккаунт.</p>

    <div style="background: #fef2f2; border-left: 4px solid #b91c1c; padding: 12px 16px; margin: 16px 0;">
        <strong>Причина блокировки:</strong>
        <p style="margin: 8px 0 0;">{{ $reason }}</p>
    </div>

    <p>Дата блокировки: <strong>{{ $bannedAt }}</strong></p>

    <p>
        Вы не сможете войти в свой аккаунт пока бан не будет снят. Если вы
        считаете что это произошло по ошибке — свяжитесь с поддержкой:
        <a href="mailto:Gamestore.help@yandex.com">Gamestore.help@yandex.com</a>
    </p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">

    <p style="color: #666; font-size: 0.9em;">
        Это автоматическое сообщение от GameStore. Не отвечайте на него.
    </p>
</body>
</html>
