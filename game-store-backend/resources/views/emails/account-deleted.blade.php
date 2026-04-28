<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аккаунт удалён</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #b91c1c;">⚠ Ваш аккаунт удалён</h1>

    <p>Здравствуйте, {{ $userName }}!</p>

    <p>
        Администрация GameStore удалила ваш аккаунт. Это окончательное и
        необратимое действие — все ваши данные удалены или анонимизированы
        в соответствии с правилами каскадного удаления базы.
    </p>

    <p>
        Если вы считаете что это произошло по ошибке или хотите узнать причину —
        свяжитесь с поддержкой:
        <a href="mailto:Gamestore.help@yandex.com">Gamestore.help@yandex.com</a>
    </p>

    <p>
        При желании вы можете создать новый аккаунт, но прежние данные
        восстановлены не будут.
    </p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">

    <p style="color: #666; font-size: 0.9em;">
        Это последнее автоматическое сообщение GameStore для этого аккаунта.
    </p>
</body>
</html>
