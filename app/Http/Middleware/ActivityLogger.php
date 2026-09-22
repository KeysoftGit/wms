<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, $response)
    {
        // Skip jika tidak perlu log
        if (!$this->shouldLog($request)) {
            return;
        }

        try {
            // Filter payload untuk sembunyikan data sensitif
            $payload = $this->filterSensitiveData($request->all());

            // Log activity
            ActivityLog::create([
                'UserID' => Auth::id() ?: 'GUEST',
                'ReffID' => $this->getReferenceId($request, $response),
                'ReffDate' => now()->toDateString(),
                'FrmName' => $this->getFormName($request),
                'Action' => $this->getAction($request),
                'EntryTime' => now(),
                'RoutePath' => $request->path(),
                'Payload' => !empty($payload)
                    ? $this->formatPayloadForStorage($payload)
                    : null,
                'Method' => $request->method(),
                'ResponseStatus' => $response->getStatusCode(),
            ]);
        } catch (\Exception $e) {
            // Log error tapi jangan ganggu aplikasi
            Log::channel('daily')->error('Activity log failed: ' . $e->getMessage());
        }
    }

    /**
     * Format payload JSON untuk storage dengan tampilan yang clean
     */
    private function formatPayloadForStorage(array $data): string
    {
        // Clean data dulu - handle escaped slashes
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                // Replace escaped forward slashes
                $value = str_replace('\/', '/', $value);
                // Replace other common escaped characters
                $value = str_replace('\"', '"', $value);
                $value = str_replace("\\'", "'", $value);
                $value = str_replace('\\\\', '\\', $value);
            }
        });

        // Encode dengan semua flag untuk clean output
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        // Tambahkan flag jika constants tersedia
        if (defined('JSON_HEX_APOS')) {
            $flags |= JSON_HEX_APOS;
        }
        if (defined('JSON_HEX_QUOT')) {
            $flags |= JSON_HEX_QUOT;
        }
        if (defined('JSON_UNESCAPED_LINE_TERMINATORS')) {
            $flags |= JSON_UNESCAPED_LINE_TERMINATORS;
        }

        $json = json_encode($data, $flags);

        // Final cleanup untuk handle kasus edge
        $json = preg_replace('/\\\\\//', '/', $json); // Unescape forward slashes
        $json = preg_replace('/\\\\\\\\/', '\\\\', $json); // Unescape backslashes

        return $json;
    }

    /**
     * Filter data sensitif dari payload
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitive = ['password', 'token', '_token', 'api_token', 'secret'];

        foreach ($sensitive as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***HIDDEN***';
            }
        }

        return $data;
    }

    /**
     * Ambil reference ID (transactionNo, id, dll)
     */
    private function getReferenceId(Request $request, $response): string
    {
        // Priority: Dari request parameters
        $reffId = $request->input('transactionNo')
            ?? $request->input('TransactionNo')
            ?? $request->input('transaction_no')
            ?? $request->input('no')
            ?? $request->input('id')
            ?? $request->route('transactionNo')
            ?? $request->route('id');

        if (!empty($reffId)) {
            return (string) $reffId;
        }

        // Default: generate dari timestamp
        return 'SYS-' . now()->format('YmdHis');
    }

    /**
     * Ambil nama form/module
     */
    private function getFormName(Request $request): string
    {
        $route = $request->route();

        // Dari route name
        if ($route && $route->getName()) {
            $routeName = $route->getName();
            $parts = explode('.', $routeName);

            // Mapping ke kode module
            $moduleMap = [
                'sales-order' => 'SO',
                'purchase-order' => 'PO',
                'work-order' => 'WO',
                'inventory' => 'INV',
                'product' => 'PROD',
                'customer' => 'CUST',
                'supplier' => 'SUPP',
                'user' => 'USER',
                'role' => 'ROLE',
                'report' => 'RPT',
                'dashboard' => 'DASH',
                'master-data' => 'MASTER',
                'transaction' => 'TRX',
            ];

            $module = $parts[0];
            return $moduleMap[$module] ?? strtoupper(substr($module, 0, 6));
        }

        // Dari URL path
        $path = $request->path();
        $segments = explode('/', $path);
        $firstSegment = $segments[0] ?? 'APP';

        return strtoupper(substr($firstSegment, 0, 6));
    }

    /**
     * Tentukan action berdasarkan HTTP method
     */
    private function getAction(Request $request): string
    {
        return match ($request->method()) {
            'POST' => 'CREATE',
            'PUT', 'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE',
            'GET' => 'VIEW',
            default => $request->method(),
        };
    }

    /**
     * Cek apakah request perlu di-log
     */
    private function shouldLog(Request $request): bool
    {
        // Skip jika ada header khusus atau debug
        if ($request->header('X-Log-Activity') === 'false') {
            return false;
        }

        $method = $request->method();
        $path = $request->path();

        // Skip GET request yang bukan report/export
        if ($method === 'GET') {
            $loggableGetPaths = ['report', 'export', 'download', 'print', 'preview'];
            foreach ($loggableGetPaths as $loggable) {
                if (str_contains($path, $loggable)) {
                    return true;
                }
            }
            return false;
        }

        // Skip excluded paths
        $excludedPaths = [
            'logout',
            'login',
            'password/',
            'assets/',
            'storage/',
            'vendor/',
            'js/',
            'css/',
            'fonts/',
            'images/',
            'favicon.ico',
            'robots.txt',
            '_debugbar/',
            'telescope/',
            'horizon/',
            'livewire/',
            'activity-log', // Skip activity log sendiri biar gak infinite loop
        ];

        foreach ($excludedPaths as $exclude) {
            if (str_starts_with($path, $exclude)) {
                return false;
            }
        }

        return true;
    }
}
