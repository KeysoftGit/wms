<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithConditionalSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ValidateSheet implements WithMultipleSheets
{
    use WithConditionalSheets;

    public function conditionalSheets(): array
    {
        return [
            'Country' => new ValidateImport(),
            'Currency' => new ValidateImport(),
            'Vehicle' => new ValidateImport(),
            'Division' => new ValidateImport(),
            'Supplier' => new ValidateImport(),
            'Employee' => new ValidateImport(),
            'Customer' => new ValidateImport(),
            'COA' => new ValidateImport(),
            'FACategory' => new ValidateImport(),
            'FALocation' => new ValidateImport(),
            'Warehouse' => new ValidateImport(),
            'Unit' => new ValidateImport(),
            'PartCategory' => new ValidateImport(),
            'PartSpecification' => new ValidateImport(),
            'PartVariant' => new ValidateImport(),
            'InventoryType' => new ValidateImport(),
            'Part' => new ValidateImport(),

            // PRICING
            'Price' => new ValidateImport(),
            'Discount' => new ValidateImport(),
            'PriceBarometer' => new ValidateImport(),

            // OPNAME
            'Opname' => new ValidateImport(),

            'BeginningStock' => new ValidateImport(),
            'Hutang' => new ValidateImport(),
            'Piutang' => new ValidateImport(),
        ];
    }

}
