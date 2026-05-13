<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AuditLogger
{
    /**
     * Formato: [ID Usuario/Rol] | [Fecha/Hora] | [IP/Browser] | [Acción Realizada]
     */
    public static function write(?string $whoIdRole, Request $request, string $action): void
    {
        try {
            $who = $whoIdRole ?? 'guest/anonymous';
            $when = now()->format('Y-m-d H:i:s');
            $ip = $request->ip();
            $browser = $request->header('User-Agent', 'unknown');

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
            //
        }
    }
}
