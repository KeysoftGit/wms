<?php

namespace App\Imports;

use App\Models\MsSupplier;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');

class ImportSupplier implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
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

        return new MsSupplier([
            'SupplierID' => $row['SupplierID'],
            'SupplierName' => $row['SupplierName'] ?? null,
            'ContactPerson' => $row['ContactPerson'] ?? null,
            'NPWP' => $row['NPWP'] ?? null,
            'Address' => $row['Address'] ?? null,
            'City' => $row['City'] ?? null,
            'CountryID' => $row['Country'] ?? null,
            'Phone' => $row['Phone'] ?? null,
            'Email' => $row['Email'] ?? null,
            'Website' => $row['Website'] ?? null,
            'Term' => $row['Term'] ?? 0,
            'LimitDaysETA' => $row['ETA'] ?? 0,
            'LimitDaysETD' => $row['ETD'] ?? 0,
            'Forwarder' => 0,
            'AccountPayableLimit' => 0,
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
