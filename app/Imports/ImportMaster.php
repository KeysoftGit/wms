<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithConditionalSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportMaster implements WithMultipleSheets, WithBatchInserts, WithChunkReading
{
    use WithConditionalSheets;

    protected $db;
    protected $UserID;

    public function __construct($db, string $UserID)
    {
        $this->db = $db;
        $this->UserID = $UserID;
    }

    public function conditionalSheets(): array
    {
        return [
            'Country' => new ImportCountry($this->db, $this->UserID),
            'Currency' => new ImportCurrency($this->db, $this->UserID),
            'Vehicle' => new ImportVehicle($this->db, $this->UserID),
            'Unit' => new ImportUnit($this->db, $this->UserID),
            'PartCategory' => new ImportPartCategory($this->db, $this->UserID),
            'PartSpecification' => new ImportPartSpecification($this->db, $this->UserID),
            'PartVariant' => new ImportPartVariant($this->db, $this->UserID),
            'InventoryType' => new ImportInventoryType($this->db, $this->UserID),
            'COA' => new ImportCOA($this->db, $this->UserID),
            'FACategory' => new ImportFACategory($this->db, $this->UserID),
            'FALocation' => new ImportFALocation($this->db, $this->UserID),
            'Warehouse' => new ImportWarehouse($this->db, $this->UserID),
            'Division' => new ImportDivision($this->db, $this->UserID),
            'Employee' => new ImportEmployee($this->db, $this->UserID),
            'Supplier' => new ImportSupplier($this->db, $this->UserID),
            'Customer' => new ImportCustomer($this->db, $this->UserID),
            'Part' => new ImportPart($this->db, $this->UserID),
            'BeginningStock' => new ImportBeginningStock($this->db, $this->UserID),
            'Hutang' => new ImportHutang($this->db, $this->UserID),
            'Piutang' => new ImportPiutang($this->db, $this->UserID),
        ];
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
