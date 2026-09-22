<?php

namespace App\Imports;

use App\Models\BukuHutang;
use App\Models\MsCOA;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');

class ImportHutang implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
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

        return new BukuHutang([
            'TransactionNo' => "AP/" . \DateTime::createFromFormat('d-m-Y', $row['TransactionDate'])->format('Ym'),
            'BalanceNo' => $row['BalanceNo'] ?? '',
            'TransactionDate' => \DateTime::createFromFormat('d-m-Y', $row['TransactionDate'])->format('Y-m-d'),
            'DueDate' => \DateTime::createFromFormat('d-m-Y', $row['DueDate'])->format('Y-m-d'),
            'SupplierID' => $row['SupplierID'] ?? '',
            'CurrencyID' => $row['CurrencyID'] ?? '',
            'Rate' => $row['Rate'] ?? 1,
            'Amount' => $row['Amount'] ?? 0,
            'isVoucher' => 0,
            'TransactionType' => 'BEGINNING',
            'CreatedBy' => $this->UserID,
            'EntryTime' => date('Y-m-d H:i:s'),
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
