@extends('emails.layout', ['title' => 'Заморозка снята'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#ffc979;letter-spacing:0.5px;">
        🔥 Заморозка снята
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        Здравствуйте, <strong style="color:#fff6df;">{{ $userName }}</strong>!
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        Совет старейшин снял заморозку с вашего аккаунта. Теперь вы снова можете
        создавать посты, комментарии и ставить реакции — все ограничения сняты.
    </p>

    <!-- CTA -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="center">
                <a href="https://game-45428688-fe94e.web.app/"
                   style="display:inline-block;padding:14px 36px;background-color:#ef4a18;color:#fff6df;font-family:Georgia,serif;font-size:14px;font-weight:bold;text-decoration:none;border-radius:6px;letter-spacing:1.5px;text-transform:uppercase;">
                    Перейти на сайт →
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0;font-size:13px;color:#9a8672;line-height:1.6;text-align:center;">
        Просим помнить о правилах сообщества — повторные нарушения могут привести к более строгим мерам.
    </p>
@endsection
