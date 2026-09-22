<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseFormatter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class DynamicConnectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guid = $request->header('X-GUID');

        if (!$guid) {
            return ResponseFormatter::error('GUID is empty', 400)->toResponse();
        }

        $result = $this->setDynamicConnection($guid);

        if (!$result['status']) {
            return ResponseFormatter::error($result['message'], 401)->toResponse();
        }

        $request->merge(['guid' => $guid]);

        return $next($request);
    }

    /**
     * Setup dynamic database connection based on GUID.
     *
     * @param string $guid
     * @return array
     */
    protected function setDynamicConnection(string $guid): array
    {
        // Ambil data client dari database keysoftone
        try {
            $client = DB::connection('keysoftone')
                ->table('clients')
                ->where('guid', $guid)
                ->first();
        } catch (\Exception $e) {
            return ['status' => false, 'message' => '[401] Failed to find client: ' . $e->getMessage()];
        }

        if (!$client) {
            return ['status' => false, 'message' => '[401] Client not found'];
        }

        if (!empty($client->subscribe_expired)) {
            $expiredDate = Carbon::parse($client->subscribe_expired);
            $now = Carbon::now();

            // Jika ada tanggalnya, cek apakah sudah terlewati
            if ($now->greaterThan($expiredDate)) {
                return [
                    'status' => false,
                    'message' => '[401] Subscription expired on ' . $expiredDate->format('Y-m-d')
                ];
            }
        }

        // Setup koneksi dinamis
        $config = [
            'driver' => 'sqlsrv',
            'host' => $client->db_host,
            'port' => $client->db_port ?? 1433,
            'database' => $client->db_database,
            'username' => $client->db_user,
            'password' => $client->db_password,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ];

        Config::set('database.connections.sqlsrv', $config);

        try {
            DB::purge('sqlsrv');
            DB::reconnect('sqlsrv');
        } catch (\Exception $e) {
            Log::info($e);
            return ['status' => false, 'message' => '[401] Failed to connect to client database: ' . $e->getMessage()];
        }

        return ['status' => true, 'connection' => 'sqlsrv', 'guid' => $guid];
    }
}
