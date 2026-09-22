<?php

namespace App\Imports;

use App\Models\MsPart;
use App\Models\MsPartUnit;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Maatwebsite\Excel\Row;

HeadingRowFormatter::default('none');

class ImportPart implements OnEachRow, WithHeadingRow, WithBatchInserts, WithChunkReading
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

        MsPart::create([
            'PartID' => $row['PartID'],
            'PartName' => $row['PartName'] ?? null,
            'OtherID' => $row['OtherID'] ?? null,
            'CategoryID' => $row['CategoryID'] ?? null,
            'SpecificationID' => $row['SpecificationID'] ?? null,
            'VariantID' => $row['VariantID'] ?? null,
            'InventoryTypeID' => $row['InventoryTypeID'] ?? null,
            'PartType' => $row['PartType'] ?? null,
            'WithSerialNo' => $row['WithSerialNumber'] ?? 0,
            'MaximumStockBuffer' => $row['MaximumStockBuffer'] ?? 0,
            'MinimumStockBuffer' => $row['MinimumStockBuffer'] ?? 0,
            'VAT2' => 11,
            'DeferedWarehouseID' => $row['DeferedWarehouseID'] ?? null,
            'Notes' => $row['Notes'] ?? null,
            'Pricing' => $row['Pricing'] ?? null,
            'TypeOfGuarantee' => $row['Guarantee'] ?? null,
            'Active' => 1,
            'CreatedBy' => $this->UserID,
            'EntryTime' => date('Y-m-d H:i:s'),
            'LastUpdateBy' => $this->UserID,
            'LastUpdate' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if($row['Unit1'] != null && $row['Unit1'] != ''){
            MsPartUnit::create([
                'PartID' => $row['PartID'],
                'UnitID1' => $row['Unit1'],
                'UnitID2' => $row['Unit1'],
                'Conversion' => 1,
                'Sequence' => 1
            ]);
        }

        if($row['Unit2'] != null && $row['Unit2'] != ''){
            MsPartUnit::create([
                'PartID' => $row['PartID'],
                'UnitID1' => $row['Unit1'],
                'UnitID2' => $row['Unit2'],
                'Conversion' => $row['Unit2Conversion'],
                'Sequence' => 2
            ]);
        }

        if($row['Unit3'] != null && $row['Unit3'] != ''){
            MsPartUnit::create([
                'PartID' => $row['PartID'],
                'UnitID1' => $row['Unit1'],
                'UnitID2' => $row['Unit3'],
                'Conversion' => $row['Unit3Conversion'],
                'Sequence' => 3
            ]);
        }

        if($row['Unit4'] != null && $row['Unit4'] != ''){
            MsPartUnit::create([
                'PartID' => $row['PartID'],
                'UnitID1' => $row['Unit1'],
                'UnitID2' => $row['Unit4'],
                'Conversion' => $row['Unit4Conversion'],
                'Sequence' => 4
            ]);
        }

        if($row['Unit5'] != null && $row['Unit5'] != ''){
            MsPartUnit::create([
                'PartID' => $row['PartID'],
                'UnitID1' => $row['Unit5'],
                'UnitID2' => $row['Unit5'],
                'Conversion' => $row['Unit5Conversion'],
                'Sequence' => 5
            ]);
        }
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
