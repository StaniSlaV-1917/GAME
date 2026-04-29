@extends('emails.layout', ['title' => $title ?? 'Новое уведомление'])

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#fff6df;letter-spacing:0.5px;">
        {{ $title }}
    </h1>

    <p style="margin:0 0 8px;font-size:15px;color:#d8c49a;">
        Здравствуйте, <strong style="color:#fff6df;">{{ $recipient ?: 'воин' }}</strong>!
    </p>

    <p style="margin:0 0 22px;font-size:15px;color:#d8c49a;">
        {{ $intro }}
    </p>

    {{-- Preview блок основного контента (текст коммента / реакции / etc) --}}
    @if (!empty($preview))
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#1a0e08" style="background-color:#1a0e08;border-left:3px solid #ef4a18;border-radius:0 4px 4px 0;margin-bottom:16px;">
        <tr>
            <td style="padding:14px 18px;">
                <p style="margin:0;font-size:14px;color:#fff6df;line-height:1.6;font-style:italic;">
                    {{ $preview }}
                </p>
            </td>
        </tr>
    </table>
    @endif

    {{-- Parent preview — только для reply, показывает родительский коммент --}}
    @if (!empty($parent_preview))
    <p style="margin:0 0 6px;font-size:11px;color:#c79a5e;text-transform:uppercase;letter-spacing:1.5px;">
        Ваш комментарий:
    </p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#0f0a07" style="background-color:#0f0a07;border-left:3px solid #5a3d22;border-radius:0 4px 4px 0;margin-bottom:22px;">
        <tr>
            <td style="padding:12px 18px;">
                <p style="margin:0;font-size:13px;color:#d8c49a;line-height:1.6;">
                    {{ $parent_preview }}
                </p>
            </td>
        </tr>
    </table>
    @endif

    {{-- CTA-кнопка --}}
    @if (!empty($cta_url))
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:8px 0 28px;">
        <tr>
            <td align="left">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td bgcolor="#ef4a18" style="background-color:#ef4a18;border-radius:4px;">
                            <a href="{{ $cta_url }}" style="display:inline-block;padding:12px 28px;font-family:Georgia,'Times New Roman',serif;font-size:15px;font-weight:bold;color:#fff6df;text-decoration:none;letter-spacing:1px;">
                                {{ $cta_label ?? 'Открыть' }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @endif

    {{-- Toggle hint - какую настройку отключить чтобы перестало приходить --}}
    <p style="margin:18px 0 0;font-size:12px;color:#7a5a36;">
        Эти уведомления можно отключить в
        <a href="{{ config('app.frontend_url') }}/profile" style="color:#c79a5e;text-decoration:underline;">настройках профиля</a>{{ !empty($pref_label) ? ' → ' . $pref_label : '' }}.
    </p>
@endsection
