@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Advanced Stock Adjustment - {{ $adjust->TransactionNo }}</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-4 p-3 bg-light rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
            <a href="{{ route('adjust') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold mb-0 flex-grow-1 ms-md-4">
                Stock Adjustment - {{ $adjust->TransactionNo }}
            </h1>
        </div>

        <div class="row justify-content-between mb-3 align-items-end">
            <div class="col-auto d-flex flex-row">
                @if($adjust->Editable == 1 && auth()->user()->hasAnyPermission(['admin', 'stock_adj.edit']))
                    <a href="{{ route('adjust.edit', ['id' => $adjust->id]) }}" class="btn btn-primary fs-6">
                        <i class="fa fa-fw fa-edit"></i> Edit
                    </a>
                @endif
                @if(auth()->user()->hasAnyPermission(['admin', 'journal.view']))
                    <button type="button" class="btn btn-alt-secondary fs-6 ms-1" onclick="showJournalModal({{ json_encode($adjust->TransactionNo) }})">
                        <i class="fa fa-fw fa-book"></i> View Journal
                    </button>
                @endif
            </div>
            <div class="col-auto">
                @include('partials.admin._transaction_print_form', [
                    'transactionNo' => $adjust->TransactionNo,
                    'options' => $options,
                ])
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content p-4">
                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="mb-3">
                            <label class="form-label">Transaction No</label>
                            <input type="text" class="form-control" value="{{ $adjust->TransactionNo }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Date</label>
                            <input type="text" class="form-control" value="{{ date('Y-m-d', strtotime($adjust->TransactionDate)) }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expired Date</label>
                            <input type="text" class="form-control" value="{{ date('Y-m-d', strtotime($adjust->ExpiredDate)) }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Division</label>
                            <input type="text" class="form-control" value="{{ $adjust->DivisionID }}{{ $adjust->division && $adjust->division->DivisionName ? ' - ' . $adjust->division->DivisionName : '' }}" readonly>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="mb-3">
                            <label class="form-label">Warehouse</label>
                            <input type="text" class="form-control" value="{{ $adjust->WarehouseID }}{{ $adjust->warehouse && $adjust->warehouse->WarehouseName ? ' - ' . $adjust->warehouse->WarehouseName : '' }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Opname</label>
                            <input type="text" class="form-control" value="{{ $adjust->StockOpnameNo ?: '-' }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Inventory Type</label>
                            <input type="text" class="form-control" value="{{ $adjust->InventoryTypeID ? ($adjust->InventoryTypeID . ($adjust->type && $adjust->type->InventoryTypeName ? ' - ' . $adjust->type->InventoryTypeName : '')) : '-' }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <input type="text" class="form-control" value="{{ $adjust->Notes }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content table-responsive">
                <table class="table table-bordered table-vcenter">
                    <thead>
                    <tr>
                        <th>Part</th>
                        <th>Unit</th>
                        @canany(['admin', 'stock_adj.show_stock'])
                            <th>Qty Stock</th>
                        @endcanany
                        <th>Qty Opname</th>
                        @canany(['admin', 'stock_adj.show_stock'])
                            <th>Difference</th>
                        @endcanany
                        <th>Qty 2</th>
                        <th>Batch No</th>
                        <th>Coil No</th>
                        <th>Serial No</th>
                        <th>Exp Date</th>
                        <th>BIN</th>
                        <th>LOC</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($adjust->details as $detail)
                        <tr>
                            <td>{{ $detail->PartID }} - {{ $detail->part->PartName ?? '-' }}</td>
                            <td>{{ $detail->UnitID }}</td>
                            @canany(['admin', 'stock_adj.show_stock'])
                                <td>{{ $detail->QtyStockFormatted }}</td>
                            @endcanany
                            <td>{{ $detail->QtyOpnameFormatted }}</td>
                            @canany(['admin', 'stock_adj.show_stock'])
                                <td>{{ $detail->DifferenceFormatted }}</td>
                            @endcanany
                            <td>{{ $detail->Qty2Formatted !== null ? $detail->Qty2Formatted . ($detail->UnitID2 ? ' ' . $detail->UnitID2 : '') : '-' }}</td>
                            <td>{{ $detail->BatchNo ?: '-' }}</td>
                            <td>{{ $detail->CoilNo ?: '-' }}</td>
                            <td>{{ $detail->SerialNo ?: '-' }}</td>
                            <td>{{ $detail->ExpDateFormatted ?: '-' }}</td>
                            <td>{{ $detail->BIN ?: '-' }}</td>
                            <td>{{ $detail->LOC ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->hasAnyPermission(['admin', 'stock_adj.show_stock']) ? 10 : 8 }}" class="text-center text-muted">No data available.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content table-responsive">
                <h5 class="mb-3">Checkers</h5>
                <table class="table table-bordered table-vcenter">
                    <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($adjust->checkers as $checker)
                        <tr>
                            <td>{{ $checker->EmployeeID }}{{ $checker->employee && $checker->employee->EmployeeName ? ' - ' . $checker->employee->EmployeeName : '' }}</td>
                            <td>{{ $checker->Status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted">No checker available.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
