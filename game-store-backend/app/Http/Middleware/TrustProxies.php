<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Fly.io proxy терминирует HTTPS на edge и форвардит к приложению
     * по HTTP, передавая X-Forwarded-Proto=https. Без 'trust everything'
     * Laravel игнорирует заголовок и строит URL'ы со схемой http://, что
     * приводит к Mixed Content на HTTPS-фронте (картинки новостей не
     * грузятся, sanctum-cookies не выставляются и т.п.).
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
