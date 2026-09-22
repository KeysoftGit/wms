<?php

namespace App\Imports;

use App\Models\BukuStock;
use App\Models\MaterialCost;
use App\Models\MsCOA;
use App\Models\MsPartUnit;
use App\Models\MsUnit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Maatwebsite\Excel\Row;

HeadingRowFormatter::default('none');

class ImportBeginningStock implements OnEachRow, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    protected $db;
    protected $UserID;

    public function __construct($db, string $UserID)
    {
        $this->db = $db;
        $this->UserID = $UserID;
    }
    public function onRow(Row $row)
    {
        DB::purge('sqlsrv');

        Config::set('database.connections.sqlsrv', $this->db);

        $unit = MsPartUnit::where('PartID', $row['PartID'])->first();
        BukuStock::create([
            'TransactionNo' => 'BEGINNING BALANCE',
            'TransactionDate' => Carbon::createFromFormat('d-m-Y', $row['TransactionDate'])->format('Y-m-d'),
            'ExpDate' => $row['ExpDate'] ? Carbon::createFromFormat('d-m-Y', $row['ExpDate'])->format('Y-m-d') : null,
            'PartID' => $row['PartID'] ?? null,
            'WarehouseID' => $row['WarehouseID'] ?? null,
            'Qty' => $row['Qty'] ?? null,
            'Sequence' => 0,
            'UnitID' => isset($unit) ? $unit->UnitID1 : null,
            'TransactionType' => 'BEGINNING',
            'Notes' => $row['Notes'],
            'CreatedBy' => $this->UserID,
            'EntryTime' => date('Y-m-d H:i:s'),
        ]);

        MaterialCost::create([
            'Period' => Carbon::createFromFormat('d-m-Y', $row['TransactionDate'])->format('Ym'),
            'PartID' => $row['PartID'] ?? null,
            'BeginningBalance' => $row['BeginningBalance'] ?? null,
            'EndingBalance' => 0,
            'TransactionDate' => Carbon::createFromFormat('d-m-Y', $row['TransactionDate'])->format('Y-m-d'),
        ]);
    }

    public function batchSize(): int
    {
        return 50;
    }
    public function chunkSize(): int
    {
        return 50;

    }
}
