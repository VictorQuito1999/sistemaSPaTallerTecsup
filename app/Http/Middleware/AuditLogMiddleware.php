<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $user = Auth::guard('sanctum')->user() ?? Auth::user();
            $who = $user
                ? $user->id.'/'.$user->role
                : 'guest/anonymous';

            $when = now()->format('Y-m-d H:i:s');
            $ip = $request->ip();
            $browser = $request->header('User-Agent', 'unknown');

            $action = $request->route()?->getName()
                ?: $request->method().' '.$request->path();

            $line = sprintf(
                "[%s] | [%s] | [%s/%s] | [%s]%s",
                $who,
                $when,
                $ip,
                $browser,
                $action,
                PHP_EOL
            );

            File::ensureDirectoryExists(storage_path('logs'));
            File::append(storage_path('logs/audit.log'), $line);
        } catch (\Throwable) {
            // No bloquear la petición por fallos de auditoría.
        }

        return $response;
    }
}
