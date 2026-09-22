<?php

namespace App\Imports;

use App\Models\MsEmployee;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');

class ImportEmployee implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
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

        return new MsEmployee([
            'EmployeeID' => $row['EmployeeID'],
            'FirstName' => $row['FirstName'] ?? null,
            'LastName' => $row['LastName'] ?? null,
            'Gender' => $row['Gender'] ??  null,
            'BirthDate' => $row['BirthDate'] ? \DateTime::createFromFormat('d-m-Y', $row['BirthDate'])->format('Y-m-d') : null,
            'BloodType' => $row['BloodType'] ?? null,
            'IDNumber' => $row['IDNumber'] ?? null,
            'NPWP' => $row['NPWP'] ?? null,
            'MatrialStatus' => $row['MatrialStatus'] ?? null,
            'Address' => $row['Address'] ?? null,
            'City' => $row['City'] ?? null,
            'CountryID' => $row['Country'] ?? null,
            'Phone1' => $row['Phone1'] ?? null,
            'Phone2' => $row['Phone2'] ?? null,
            'Email' => $row['Email'] ?? null,
            'HireDate' => $row['HireDate'] ? \DateTime::createFromFormat('d-m-Y', $row['HireDate'])->format('Y-m-d') : null,
            'ActiveDate' => $row['ActiveDate'] ? \DateTime::createFromFormat('d-m-Y', $row['ActiveDate'])->format('Y-m-d') : null,
            'DivisionID' => $row['DivisionID'] ?? null,
            'ReferenceID' => $row['ReferenceID'] ?? null,
            'SupervisorID' => $row['SupervisorID'] ?? null,
            'Position' => $row['Position'] ?? null,
            'BankID' => $row['BankID'] ?? null,
            'BankName' => $row['BankName'] ?? null,
            'BankAccountNo' => $row['BankAccountNo'] ?? null,
            'BankAccountOwner' => $row['BankAccountOwner'] ?? null,
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
