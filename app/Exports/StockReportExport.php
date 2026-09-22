<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StockReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping, WithColumnFormatting
{
    protected $data;
    private $index = 0;

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
            'NO',
            'ID',
            'DATE',
            'PART ID',
            'PART NAME',
            'UNIT',
            'CONVERSION',
            'WAREHOUSE',
            'REPORT QTY',
            'NOTES',
            'PIC',
            'ENTRY TIME'
        ];
    }

    public function map($row): array
    {
        $this->index++;

        return [
            $this->index,
            $row->id,
            $row->Date ? \Carbon\Carbon::parse($row->Date)->format('d/m/Y') : '',
            $row->PartID,
            $row->part->PartName ?? '-',
            $row->UnitID . '-' . ($row->unit->UnitName ?? '-'),
            auto_numeric_format($row->Conversion ?? 0),
            $row->WarehouseID . '-' . ($row->warehouse->WarehouseName ?? '-'),
            auto_numeric_format($row->Qty ?? 0),
            $row->Notes,
            $row->LastUpdateBy ?? '-',
            $row->updated_at ? $row->updated_at->format('d/m/Y H:i:s') : '-'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER, // KONVERSI
            'I' => NumberFormat::FORMAT_NUMBER, // HASIL OPNAME
            'J' => NumberFormat::FORMAT_TEXT, // NOTES
        ];
    }
}
