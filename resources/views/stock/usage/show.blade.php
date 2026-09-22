@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Advanced Part Usage</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-4 p-3 bg-light rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
            <a href="{{ route('usage') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold mb-0 flex-grow-1 ms-md-4">
                Part Usage - {{ $data->TransactionNo }}
            </h1>
        </div>

        <div class="row justify-content-between mb-3 align-items-end">
            <div class="col-auto d-flex flex-row">
                @if ($data->Editable == 1 && auth()->user()->hasAnyPermission(['admin', 'part_usage.edit']))
                    <a href="{{ route('usage.edit', ['id' => $data->id]) }}" class="btn btn-primary fs-6">
                        <i class="fa fa-fw fa-edit"></i> Edit
                    </a>
                @endif
            </div>

            <div class="col-auto">
                @include('partials.admin._transaction_print_form', [
                    'transactionNo' => $data->TransactionNo,
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
                            <input type="text" class="form-control" value="{{ $data->TransactionNo }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Date</label>
                            <input type="text" class="form-control" value="{{ $data->TransactionDateFormatted }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expired Date</label>
                            <input type="text" class="form-control" value="{{ $data->ExpiredDateFormatted }}" readonly>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="mb-3">
                            <label class="form-label">WO Number</label>
                            <input type="text" class="form-control" value="{{ $data->WONumber }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Division</label>
                            <input type="text" class="form-control" value="{{ $data->DivisionID }}{{ $data->division ? ' - ' . $data->division->DivisionName : '' }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <input type="text" class="form-control" value="{{ $data->Notes }}" readonly>
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
                            <th>Qty</th>
                            <th>Qty 2</th>
                            <th>Warehouse</th>
                            <th>Batch No</th>
                            <th>Coil No</th>
                            <th>Serial No</th>
                            <th>Exp Date</th>
                            <th>BIN</th>
                            <th>LOC</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data->details as $detail)
                            <tr>
                                <td>{{ $detail->PartID }} - {{ $detail->part->PartName ?? '-' }}</td>
                                <td>{{ $detail->UnitID }} - {{ $detail->unit->UnitName ?? '-' }}</td>
                                <td>{{ $detail->QtyFormatted }}</td>
                                <td>{{ $detail->Qty2Formatted !== null ? $detail->Qty2Formatted . ($detail->UnitID2 ? ' ' . $detail->UnitID2 : '') : '-' }}</td>
                                <td>{{ $detail->warehouse->WarehouseName ?? '-' }}</td>
                                <td>{{ $detail->BatchNo ?: '-' }}</td>
                                <td>{{ $detail->CoilNo ?: '-' }}</td>
                                <td>{{ $detail->SerialNo ?: '-' }}</td>
                                <td>{{ $detail->ExpDateFormatted ?: '-' }}</td>
                                <td>{{ $detail->BIN ?: '-' }}</td>
                                <td>{{ $detail->LOC ?: '-' }}</td>
                                <td>{{ $detail->Notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">No data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
