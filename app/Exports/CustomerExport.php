<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
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
            'Customer ID',
            'Customer Name',
            'Birthday',
            'Active',
            'Contact Person',
            'Address',
            'City',
            'Phone',
            'Email',
            'NPWP',
            'Term',
            'Credit Limit',
            'Invoice Limit',
            'Division',
            'Salesman',
            'Created At'
        ];
    }

    public function map($row): array
    {
        return [
            $row->CustomerID,
            $row->CustomerName,
            $row->Birthday ? date('Y-m-d', strtotime($row->Birthday)) : '',
            $row->Active == 1 ? 'YES' : 'NO',
            $row->ContactPerson,
            $row->Address,
            $row->City,
            $row->Phone,
            $row->Email,
            $row->NPWP,
            $row->Term,
            $row->CreditLimit,
            $row->InvoiceLimit,
            $row->division ? $row->division->DivisionID . ' - ' . $row->division->DivisionName : '',
            $row->salesman ? $row->salesman->EmployeeID . ' - ' . $row->salesman->EmployeeName : '',
            $row->created_at ? $row->created_at->format('d/m/Y') : ''
        ];
    }
}
