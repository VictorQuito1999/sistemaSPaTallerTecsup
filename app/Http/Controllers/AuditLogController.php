<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /** Máximo de líneas leídas desde el final del archivo (ventana en memoria). */
    private const READ_TAIL_MAX = 500;

    private const PER_PAGE_DEFAULT = 50;

    private const PER_PAGE_MAX = 100;

    /**
     * Lista entradas recientes de storage/logs/audit.log (formato AuditLogger / AuditLogMiddleware).
     *
     * Formato por línea: [%s] | [%s] | [%s/%s] | [%s]  → quien | fecha | ip/ua | acción
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? self::PER_PAGE_DEFAULT);
        $perPage = min(max($perPage, 1), self::PER_PAGE_MAX);

        $path = storage_path('logs/audit.log');

        if (! is_readable($path)) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                ],
            ]);
        }

        $rawLines = $this->readLastLines($path, self::READ_TAIL_MAX);
        $parsed = [];

        foreach ($rawLines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $entry = $this->parseAuditLine($line);
            if ($entry !== null) {
                $parsed[] = $entry;
            }
        }

        // El archivo crece hacia abajo: las últimas líneas son las más recientes.
        $parsed = array_reverse($parsed);

        $total = count($parsed);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($parsed, $offset, $perPage);

        $rows = [];
        foreach ($slice as $index => $row) {
            $row['id'] = $offset + $index + 1;
            $rows[] = $row;
        }

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    private function readLastLines(string $filepath, int $lines): array
    {
        $handle = fopen($filepath, 'rb');
        if ($handle === false) {
            return [];
        }

        $buffer = '';
        $chunkSize = 8192;

        fseek($handle, 0, SEEK_END);
        $position = ftell($handle);

        while ($position > 0 && substr_count($buffer, "\n") <= $lines + 2) {
            $readLength = min($chunkSize, $position);
            $position -= $readLength;
            fseek($handle, $position);
            $buffer = fread($handle, $readLength).$buffer;
        }

        fclose($handle);

        $parts = preg_split("/\r\n|\n|\r/", $buffer);
        $parts = array_values(array_filter($parts, fn ($l) => trim((string) $l) !== ''));

        if (count($parts) > $lines) {
            $parts = array_slice($parts, -$lines);
        }

        return $parts;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseAuditLine(string $line): ?array
    {
        $pattern = '/^\[(?<who>.+?)\] \| \[(?<when>.+?)\] \| \[(?<ip_ua>.+?)\] \| \[(?<action>.+?)\]\s*$/';

        if (! preg_match($pattern, $line, $m)) {
            return null;
        }

        $who = trim($m['who']);
        $when = trim($m['when']);
        $ipUa = trim($m['ip_ua']);
        $action = trim($m['action']);

        $slashPos = strpos($ipUa, '/');
        $ip = $slashPos === false ? $ipUa : substr($ipUa, 0, $slashPos);
        $userAgent = $slashPos === false ? '' : substr($ipUa, $slashPos + 1);

        return [
            'occurred_at' => $when,
            'user_identity' => $who,
            'action' => $action,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'event_category' => $this->categorizeAction($action),
        ];
    }

    private function categorizeAction(string $action): string
    {
        $lower = strtolower($action);

        if (str_contains($lower, 'completed')
            || str_contains($lower, 'verified')
            || str_contains($lower, 'activation.complete')
            || str_contains($lower, 'success')
            || str_contains($lower, 'created')
            || str_contains($lower, 'updated')) {
            return 'success';
        }

        if (str_contains($lower, 'login')
            || str_contains($lower, '.auth.')
            || str_contains($lower, 'google2fa')) {
            return 'login';
        }

        if (str_contains($lower, 'change-password')
            || str_contains($lower, 'complete-activation')
            || (str_contains($lower, 'password') && ! str_contains($lower, 'login'))) {
            return 'password';
        }

        return 'other';
    }
}
