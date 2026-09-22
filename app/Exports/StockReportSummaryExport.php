<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StockReportSummaryExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping, WithColumnFormatting
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
            'DATE',
            'PART ID',
            'PART NAME',
            'UNIT',
            'WAREHOUSE',
            'REPORT QTY',
            'INVENTORY',
            'DIFFERENCE',
            'NOTES'
        ];
    }

    public function map($row): array
    {
        $this->index++;

        return [
            $this->index,
            date('d/m/Y', strtotime($row->Date)),
            $row->PartID,
            $row->part->PartName ?? '-',
            $row->satuan_value,
            $row->WarehouseID . ' - ' . ($row->warehouse->WarehouseName ?? '-'),
            $row->Qty,
            $row->inventory_value,
            $row->inventory_diff_value,
            $row->all_notes_value
        ];
    }

    public function columnFormats(): array
    {
        $formats = [
            'G' => NumberFormat::FORMAT_NUMBER, // HASIL OPNAME
        ];

        $formats['H'] = NumberFormat::FORMAT_NUMBER; // INVENTORY
        $formats['I'] = NumberFormat::FORMAT_NUMBER; // SELISIH
        $formats['J'] = NumberFormat::FORMAT_TEXT;   // NOTES

        return $formats;
    }
}
