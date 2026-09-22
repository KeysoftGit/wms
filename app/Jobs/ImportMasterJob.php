<?php

namespace App\Jobs;

use Throwable;
use App\Models\Import;
use App\Imports\ImportMaster;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ImportMasterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 3600;

    protected string $type;
    protected string $file;
    protected string $id;
    protected $db;
    protected $UserID;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $type, string $file, string $id, $db, string $UserID)
    {
        $this->type = $type;
        $this->file = $file;
        $this->id = $id;
        $this->db = $db;
        $this->UserID = $UserID;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Masuk ' . $this->id);
        try {
            $import = new ImportMaster($this->db, $this->UserID);
            $import->onlySheets($this->type);

            if (!Storage::disk('import_excel')->exists($this->file)) {
                Log::error('File not found: ' . $this->file);
                throw new \Exception('File not found');
            }

            Excel::import($import, $this->file, 'import_excel');

            Import::where('job_id', $this->id)->update([
                'status' => 'done'
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            Import::where('job_id', $this->id)->update([
                'status' => 'failed'
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error($exception);

        Import::where('job_id', $this->id)->update([
            'status' => 'failed'
        ]);
    }
}
