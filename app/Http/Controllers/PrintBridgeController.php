<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class PrintBridgeController extends Controller
{
    private const MODULE_PERMISSIONS = [
        'gr' => 'gr.view',
        'do' => 'do.view',
        'part_usage' => 'part_usage.view',
        'transfer' => 'transfer.view',
        'transfer_request' => 'transfer_request.view',
        'transfer_execute' => 'transfer_execute.view',
        'transfer_receive' => 'transfer_receive.view',
        'opname' => 'opname.view',
        'stock_adj' => 'stock_adj.view',
    ];

    public function redirect(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string'],
            'transaction' => ['required', 'string'],
            'module' => ['required', 'string', 'in:' . implode(',', array_keys(self::MODULE_PERMISSIONS))],
        ]);

        $permission = self::MODULE_PERMISSIONS[$data['module']];
        abort_unless($request->user()->hasAnyPermission(['admin', $permission]), 403);
        abort_unless(
            Schema::hasTable('PrintReport') && Schema::hasTable('PrintReportMapping'),
            404
        );

        $mapped = DB::table('PrintReport as reports')
            ->join('PrintReportMapping as mappings', 'mappings.print_report_id', '=', 'reports.id')
            ->join('menus', 'menus.id', '=', 'mappings.module_id')
            ->where('reports.slug', $data['slug'])
            ->where('menus.Name', $data['module'])
            ->exists();
        abort_unless($mapped, 404);

        $secret = (string) config('app.print_bridge_secret');
        $onlineUrl = rtrim((string) config('app.keyone_online_url'), '/');
        abort_if($secret === '' || $onlineUrl === '', 503, 'Print bridge is not configured.');

        $params = [
            'guid' => (string) session('guid'),
            'slug' => $data['slug'],
            'transaction' => $data['transaction'],
            'module' => $data['module'],
            'user_id' => (string) $request->user()->getAuthIdentifier(),
            'expires' => time() + 120,
        ];
        $params['signature'] = hash_hmac('sha256', implode('|', array_values($params)), $secret);

        $templateUrl = $onlineUrl . '/print-bridge/template?' . http_build_query($params);
        $token = Str::random(64);
        $tempDirectory = storage_path('app/temp');
        $odtPath = $tempDirectory . DIRECTORY_SEPARATOR . 'print_bridge_' . $token . '.odt';
        $pdfPath = $tempDirectory . DIRECTORY_SEPARATOR . 'print_bridge_' . $token . '.pdf';

        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0775, true);
        }

        try {
            Log::info('PRINT_BRIDGE_TEMPLATE_URL', [
                'template_url' => $templateUrl,
            ]);
            $response = Http::timeout(90)
                ->withHeaders([
                    'Host' => 'online.keyone.id',
                ])->withOptions([
                    'verify' => false,
                ])
                ->get($templateUrl);

            $response->throw();
            file_put_contents($odtPath, $response->body());

            $this->convertToPdf($odtPath, $tempDirectory);
            abort_unless(file_exists($pdfPath), 500, 'PDF preview was not generated.');
        } finally {
            if (file_exists($odtPath)) {
                @unlink($odtPath);
            }
        }

        session()->put("print_report_temp_files.{$token}", [
            'path' => $pdfPath,
            'filename' => $data['slug'] . '.pdf',
            'mime' => 'application/pdf',
        ]);

        $report = DB::table('PrintReport')->where('slug', $data['slug'])->first();
        $pdfData = base64_encode(file_get_contents($pdfPath));
        $downloadUrl = route('print_bridge.download', ['token' => $token]);
        $cleanupUrl = route('print_bridge.cleanup', ['token' => $token]);

        return view('print_bridge.preview', compact(
            'report',
            'pdfData',
            'downloadUrl',
            'cleanupUrl'
        ));
    }

    public function download(string $token)
    {
        $file = session("print_report_temp_files.{$token}");
        abort_unless($this->isValidTempFile($file), 404);

        return response()->download($file['path'], $file['filename'], [
            'Content-Type' => $file['mime'],
        ]);
    }

    public function cleanup(string $token)
    {
        $file = session("print_report_temp_files.{$token}");

        if ($this->isValidTempFile($file)) {
            @unlink($file['path']);
        }

        session()->forget("print_report_temp_files.{$token}");

        return response()->json(['status' => 'success']);
    }

    private function convertToPdf(string $filePath, string $outputDirectory): string
    {
        $userProfileDirectory = $outputDirectory . DIRECTORY_SEPARATOR . 'lo_profile';
        if (!is_dir($userProfileDirectory)) {
            mkdir($userProfileDirectory, 0775, true);
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $userProfilePath = str_replace('\\', '/', $userProfileDirectory);

        if ($isWindows) {
            @exec('taskkill /F /IM soffice.bin /T 2>nul');
            @exec('taskkill /F /IM soffice.exe /T 2>nul');
        }

        $commands = $isWindows
            ? [
                (string) config('app.libreoffice_binary'),
                'C:\Program Files\LibreOffice\program\soffice.exe',
                'soffice.exe',
            ]
            : ['soffice', '/usr/bin/soffice', '/usr/local/bin/soffice'];
        $errorMessages = [];

        foreach (array_unique($commands) as $command) {
            if ($isWindows && str_contains($command, ':\\') && !file_exists($command)) {
                continue;
            }

            $process = new Process([
                $command,
                '--headless',
                '--invisible',
                '--nocrashreport',
                '--nodefault',
                '--nologo',
                '--nofirststartwizard',
                '--norestore',
                '-env:UserInstallation=file://' . ($isWindows ? '/' : '') . $userProfilePath,
                '--convert-to',
                'pdf',
                '--outdir',
                str_replace('\\', '/', $outputDirectory),
                str_replace('\\', '/', $filePath),
            ]);

            $environment = [
                'HOME' => $outputDirectory,
                'TEMP' => $outputDirectory,
                'TMP' => $outputDirectory,
            ];

            if ($isWindows) {
                $environment['USERPROFILE'] = $outputDirectory;
                $environment['APPDATA'] = $outputDirectory;
            } else {
                $environment['LD_LIBRARY_PATH'] = '';
                $environment['LD_PRELOAD'] = '';
                $environment['PATH'] = '/usr/local/bin:/usr/bin:/bin';
            }

            $process->setEnv($environment);
            $process->setTimeout(60);
            $process->run();

            $pdfPath = $outputDirectory . DIRECTORY_SEPARATOR .
                pathinfo($filePath, PATHINFO_FILENAME) . '.pdf';

            if ($process->isSuccessful() && file_exists($pdfPath)) {
                @unlink($filePath);
                return $pdfPath;
            }

            $error = trim($process->getErrorOutput() . ' ' . $process->getOutput());
            $errorMessages[] = '[' . $command . '] ' .
                ($error !== '' ? $error : 'Exit Code: ' . $process->getExitCode());
        }

        throw new \RuntimeException(
            'LibreOffice failed to generate preview: ' . implode(' | ', $errorMessages)
        );
    }

    private function isValidTempFile($file): bool
    {
        if (!is_array($file) || empty($file['path']) || !file_exists($file['path'])) {
            return false;
        }

        $tempDirectory = realpath(storage_path('app/temp'));
        $filePath = realpath($file['path']);

        return $tempDirectory !== false &&
            $filePath !== false &&
            str_starts_with($filePath, $tempDirectory . DIRECTORY_SEPARATOR);
    }
}
