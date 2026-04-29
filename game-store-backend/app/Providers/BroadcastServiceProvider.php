<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Phase 4 / Batch C — broadcast auth через Sanctum bearer token.
        // Default Broadcast::routes() ставит middleware ['web','auth']
        // (session-based), а у нас фронт на Firebase + бэк на Fly = cross-
        // domain, используем токеновую auth. Endpoint станет
        // POST /api/broadcasting/auth, фронт через axios шлёт Bearer-token.
        Broadcast::routes([
            'middleware' => ['auth:sanctum'],
            'prefix'     => 'api',
        ]);

        require base_path('routes/channels.php');
    }
}
