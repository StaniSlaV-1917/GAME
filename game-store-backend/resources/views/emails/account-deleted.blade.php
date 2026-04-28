@extends('emails.layout', ['title' => 'Аккаунт удалён'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#ef4a18;letter-spacing:0.5px;">
        ⚠ Ваш аккаунт удалён
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        Здравствуйте, <strong style="color:#fff6df;">{{ $userName }}</strong>.
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        Совет старейшин принял решение об удалении вашего аккаунта.
        Это <strong style="color:#ff8433;">необратимое действие</strong> — все ваши данные удалены или
        анонимизированы согласно правилам каскадного удаления базы данных.
    </p>

    <!-- Что произошло -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td bgcolor="#2a0a08" style="background-color:#2a0a08;padding:18px 22px;border:1px solid #ef4a18;border-left:4px solid #ef4a18;border-radius:6px;">
                <p style="margin:0 0 10px;font-size:13px;color:#ff8433;font-weight:bold;text-transform:uppercase;letter-spacing:1px;">
                    Что было удалено:
                </p>
                <p style="margin:0;font-size:13px;color:#fff6df;line-height:1.7;">
                    • Ваш профиль и личные данные<br>
                    • История заказов<br>
                    • Все ваши посты, комментарии и реакции<br>
                    • Сессии и токены доступа
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0;font-size:13px;color:#9a8672;line-height:1.6;">
        Если вы считаете что удаление произошло по ошибке или хотите узнать причину — свяжитесь с поддержкой:
        <a href="mailto:Gamestore.help@yandex.com" style="color:#ff8433;text-decoration:underline;">Gamestore.help@yandex.com</a>
    </p>

    <p style="margin:14px 0 0;font-size:13px;color:#9a8672;line-height:1.6;">
        При желании вы можете создать новый аккаунт, но восстановить прежние данные не получится.
    </p>

    <p style="margin:18px 0 0;font-size:12px;color:#5c4a32;font-style:italic;text-align:center;">
        Это последнее автоматическое сообщение от GameStore для этого аккаунта.
    </p>
@endsection
