<?php

namespace App\Imports;

use App\Models\MsWarehouse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');

class ImportWarehouse implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    protected $db;
    protected $UserID;

    public function __construct($db, string $UserID)
    {
        $this->db = $db;
        $this->UserID = $UserID;
    }
    public function model(array $row)
    {
        DB::purge('sqlsrv');

        Config::set('database.connections.sqlsrv', $this->db);

        return new MsWarehouse([
            'WarehouseID' => $row['WarehouseID'],
            'WarehouseName' => $row['WarehouseName'] != '' ? $row['WarehouseName'] : null,
            'Location' => $row['Location'] != '' ? $row['Location'] : null,
            'ParentID' => $row['ParentID'] ?? null,
            'StaffInChargeID' => $row['StaffInChargeID'] ?? null,
            'Notes' => $row['Notes'] ?? null,
            'Active' => 1,
            'IsAuto' => 0,
            'LastDigit' => null,
            'CreatedBy' => $this->UserID,
            'EntryTime' => date('Y-m-d H:i:s'),
            'LastUpdateBy' => $this->UserID,
            'LastUpdate' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
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
