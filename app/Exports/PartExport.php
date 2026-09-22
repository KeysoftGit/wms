<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class PartExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Part ID',
            'Part Name',
            'Active',
            'Category',
            'Specification',
            'Variant',
            'Inventory Type',
            'Minimum Stock Buffer',
            'Maximum Stock Buffer',
            'VAT',
            'Notes',
            'Created At'
        ];
    }

    public function map($row): array
    {
        return [
            $row->PartID,
            $row->PartName,
            $row->Active == 1 ? 'YES' : 'NO',
            $row->category ? $row->category->CategoryID . ' - ' . $row->category->CategoryName : '',
            $row->specification ? $row->specification->SpecificationID . ' - ' . $row->specification->SpecificationName : '',
            $row->variant ? $row->variant->VariantID . ' - ' . $row->variant->VariantName : '',
            $row->type ? $row->type->InventoryTypeID . ' - ' . $row->type->InventoryTypeName : '',
            $row->MinimumStockBuffer,
            $row->MaximumStockBuffer,
            $row->VAT2,
            $row->Notes,
            $row->created_at ? $row->created_at->format('d/m/Y') : ''
        ];
    }
}
