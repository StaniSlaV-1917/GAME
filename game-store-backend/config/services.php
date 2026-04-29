<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    |
    | Защита от ботов на формах регистрации, логина, поддержки. Бесплатная,
    | без лимитов. site_key публичный (попадает в JS-бандл клиентам), secret
    | приватный (бэк проверяет токены через Cloudflare API).
    |
    */
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret'   => env('TURNSTILE_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Crypto Payments (Pay/A)
    |--------------------------------------------------------------------------
    |
    | USDT TRC-20 self-hosted. Юзер платит на наш TRON-адрес уникальную
    | дробную сумму. Раз в 30 сек worker дёргает TronGrid и матчит.
    |
    | tron_recipient_address — публичный адрес кошелька (хранится в env,
    |   не secret; светится юзерам в QR-коде, ничего тайного нет).
    | trongrid_api_key       — приватный ключ для повышения rate limit
    |   (без него ~5 req/sec, с ним 100 req/sec, бесплатно).
    | min_confirmations      — кол-во подтверждений в блокчейне до того
    |   как платёж считается успешным. 1 = ~3 сек на TRC-20, для сумм
    |   <$100 безопасно. Для крупных платежей повысить до 19+ (~1 мин).
    | payment_ttl_minutes    — окно оплаты. После истечения pending →
    |   expired, юзер делает новый.
    |
    */
    'crypto' => [
        // Tron — для USDT TRC-20 + native TRX (один адрес)
        'tron_recipient_address' => env('CRYPTO_TRON_RECIPIENT'),
        'trongrid_api_key'       => env('TRONGRID_API_KEY'),

        // BSC — для USDT BEP-20 (опционально; если адрес пустой —
        // BEP-20 не будет в селекторе валют у юзера)
        'bsc_recipient_address'  => env('CRYPTO_BSC_RECIPIENT'),
        'bscscan_api_key'        => env('BSCSCAN_API_KEY'),

        'min_confirmations'      => (int) env('CRYPTO_MIN_CONFIRMATIONS', 1),
        'payment_ttl_minutes'    => (int) env('CRYPTO_PAYMENT_TTL_MINUTES', 15),
    ],

];
