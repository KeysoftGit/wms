@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Advanced Stock Opname - {{ $opname->TransactionNo }}</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-4 p-3 bg-light rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
            <a href="{{ route('opname') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold mb-0 flex-grow-1 ms-md-4">
                Stock Opname - {{ $opname->TransactionNo }}
            </h1>
        </div>

        <div class="row justify-content-between mb-3 align-items-end">
            <div class="col-auto d-flex flex-row">
                @if ($opname->Editable == 1 && auth()->user()->hasAnyPermission(['admin', 'opname.edit']))
                    <a href="{{ route('opname.edit', ['id' => $opname->id]) }}" class="btn btn-primary fs-6">
                        <i class="fa fa-fw fa-edit"></i> Edit
                    </a>
                @endif
            </div>

            <div class="col-auto">
                @include('partials.admin._transaction_print_form', [
                    'transactionNo' => $opname->TransactionNo,
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
                            <input type="text" class="form-control" value="{{ $opname->TransactionNo }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Date</label>
                            <input type="text" class="form-control" value="{{ date('Y-m-d', strtotime($opname->TransactionDate)) }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control" value="{{ $opname->Status }}" readonly>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="mb-3">
                            <label class="form-label">Warehouse</label>
                            <input type="text" class="form-control" value="{{ $opname->WarehouseID }}{{ $opname->warehouse && $opname->warehouse->WarehouseName ? ' - ' . $opname->warehouse->WarehouseName : '' }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason For Delays</label>
                            <input type="text" class="form-control" value="{{ $opname->ReasonsForDelays }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <input type="text" class="form-control" value="{{ $opname->Notes }}" readonly>
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
                            <th>Qty Stock</th>
                            <th>Qty Opname</th>
                            <th>Difference</th>
                            <th>Batch No</th>
                            <th>Coil No</th>
                            <th>Closed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($opname->details as $detail)
                            <tr>
                                <td>{{ $detail->PartID }} - {{ $detail->part->PartName ?? '-' }}</td>
                                <td>{{ $detail->UnitID }}{{ $detail->unit && $detail->unit->UnitName ? ' - ' . $detail->unit->UnitName : '' }}</td>
                                <td>{{ $detail->QtyStockFormatted }}</td>
                                <td>{{ $detail->QtyOpnameFormatted }}</td>
                                <td>{{ $detail->DifferenceFormatted }}</td>
                                <td>{{ $detail->BatchNo ?: '-' }}</td>
                                <td>{{ $detail->CoilNo ?: '-' }}</td>
                                <td>{{ $detail->Closed ? 'Yes' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">No data available.</td>
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
                        @forelse ($opname->checkers as $checker)
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
