@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Item Transfer Execute Detail</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('transfer_execute') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">
                Item Transfer Execute Detail - {{ $transfer->TransactionNo }}
            </h1>
            @if($transfer->isEditable && auth()->user()->hasAnyPermission(['admin', 'transfer_execute.edit']))
                <a href="{{ route('transfer_execute.edit', $transfer->id) }}" class="btn btn-primary ms-auto"><i class="fa fa-fw fa-edit me-1"></i>Edit</a>
            @endif
        </div>

        <div class="d-flex justify-content-end mb-3">
            @include('partials.admin._transaction_print_form', [
                'transactionNo' => $transfer->TransactionNo,
                'options' => $options,
            ])
        </div>

        <div class="block block-rounded">
            <div class="block-content pb-3">
                <div class="row">
                    <div class="col-lg-6 col-12">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="30%">Transaction No</th>
                                <td>: {{ $transfer->TransactionNo }}</td>
                            </tr>
                            <tr>
                                <th>Transaction Date</th>
                                <td>: {{ \Carbon\Carbon::parse($transfer->TransactionDate)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Request No</th>
                                <td>: {{ $transfer->RequestNo }}</td>
                            </tr>
                            <tr>
                                <th>Staff In Charge</th>
                                <td>: {{ $transfer->staffInChargeFrom->EmployeeName ?? $transfer->StaffInChargeFrom }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-lg-6 col-12">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="30%">Warehouse From</th>
                                <td>: {{ $transfer->requestHD->warehouseFrom->WarehouseName ?? $transfer->requestHD->WarehouseIDFrom }}</td>
                            </tr>
                            <tr>
                                <th>Warehouse To</th>
                                <td>: {{ $transfer->requestHD->warehouseTo->WarehouseName ?? $transfer->requestHD->WarehouseIDTo }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Items</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Part</th>
                            <th>Unit</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Total Qty</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($transfer->details as $detail)
                            <tr>
                                <td>{{ $detail->part->PartName ?? $detail->PartID }}</td>
                                <td>{{ $detail->unit->UnitName ?? $detail->UnitID }}</td>
                                <td class="text-end">{{ auto_numeric_format($detail->Qty) }}</td>
                                <td class="text-end">{{ auto_numeric_format($detail->Qty * ($detail->Conversion ?? 1)) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
