<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Http\Middleware;

use Capell\AuthenticationLog\Models\AuthenticationLog;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;

class AdminActivityMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (Filament::auth()->check()) {
            $this->updateUserActivity($request);
        }

        return $next($request);
    }

    private function updateUserActivity(Request $request): void
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if ($user === null) {
            return;
        }

        $ip = config('authentication-log.behind_cdn')
            ? (string) $request->server(config('authentication-log.behind_cdn.http_header_field'))
            : (string) $request->ip();

        $userAgent = (string) $request->userAgent();

        $now = now();

        $log = AuthenticationLog::query()
            ->where('authenticatable_type', $user->getMorphClass())
            ->where('authenticatable_id', method_exists($user, 'getKey') ? $user->getKey() : null)
            ->where('ip_address', $ip)
            ->where('user_agent', $userAgent)
            ->where('login_at', '<', $now)
            ->latest('id')
            ->first();

        if ($log !== null) {
            $log->last_seen_at = $now;
            $log->save();
        }
    }
}
