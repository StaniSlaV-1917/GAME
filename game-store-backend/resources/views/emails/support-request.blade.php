@extends('emails.layout', ['title' => 'Новое обращение в поддержку'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#fff6df;letter-spacing:0.5px;">
        📜 Новое обращение в поддержку
    </h1>

    <p style="margin:0 0 24px;font-size:15px;color:#d8c49a;">
        Юзер обратился в Вестник. Содержание ниже:
    </p>

    <!-- Метаинфо -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#1a0e08" style="background-color:#1a0e08;border:1px solid #3d3129;border-radius:6px;">
        <tr>
            <td style="padding:18px 22px;">
                <table role="presentation" cellpadding="6" cellspacing="0" border="0" width="100%" style="font-size:14px;">
                    @if($userName)
                    <tr>
                        <td width="35%" style="color:#c79a5e;font-size:12px;text-transform:uppercase;letter-spacing:1px;">От:</td>
                        <td style="color:#fff6df;">{{ $userName }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td width="35%" style="color:#c79a5e;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Email:</td>
                        <td style="color:#fff6df;">
                            <a href="mailto:{{ $userEmail }}" style="color:#ff8433;text-decoration:none;">{{ $userEmail }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#c79a5e;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Раздел:</td>
                        <td style="color:#fff6df;">{{ $problemPath }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Текст обращения -->
    <p style="margin:24px 0 12px;font-size:11px;letter-spacing:3px;color:#c79a5e;text-transform:uppercase;font-weight:bold;">
        Сообщение
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td bgcolor="#0e0a08" style="background-color:#0e0a08;padding:18px 22px;border:1px solid #3d3129;border-radius:6px;border-left:3px solid #ef4a18;">
                <p style="margin:0;font-size:14px;color:#fff6df;line-height:1.65;white-space:pre-wrap;">{!! e($body) !!}</p>
            </td>
        </tr>
    </table>

    <!-- CTA для админа -->
    <p style="margin:24px 0 0;font-size:13px;color:#9a8672;">
        Ответить можно прямо нажав <a href="mailto:{{ $userEmail }}" style="color:#ff8433;text-decoration:underline;">«Reply»</a>
        или через админку:
        <a href="https://game-45428688-fe94e.web.app/admin/support" style="color:#ff8433;text-decoration:underline;">/admin/support</a>
    </p>
@endsection
