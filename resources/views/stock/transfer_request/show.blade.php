@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Item Transfer Request - {{ $request->TransactionNo }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('transfer_request') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">{{ $request->TransactionNo }}</h1>
        </div>

        <div class="row justify-content-between mb-3 align-items-end">
            <div class="col-auto">
                <div class="d-flex flex-row">
                    @if(auth()->user()->hasAnyPermission(['admin', 'transfer_request.edit']) && $request->is_editable)
                        <a href="{{ route('transfer_request.edit', $request->id) }}" class="btn btn-primary fs-6"><i class="fa fa-fw fa-edit"></i> Edit</a>
                    @endif
                </div>
            </div>
            <div class="col-auto ms-auto">
                @include('partials.admin._transaction_print_form', [
                    'transactionNo' => $request->TransactionNo,
                    'options' => $options,
                ])
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content pb-3">
                <div class="row">
                    <div class="col-lg-6 col-12">
                        <div class="mb-3">
                            <label class="form-label">Transaction No</label>
                            <input type="text" class="form-control" value="{{ $request->TransactionNo }}" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="row mb-3">
                            <div class="col-lg-6 col-12">
                                <label class="form-label">Transaction Date</label>
                                <input type="text" class="form-control" value="{{ date('d/m/Y', strtotime($request->TransactionDate)) }}" readonly>
                            </div>
                            <div class="col-lg-6 col-12">
                                <label class="form-label">Expire Date</label>
                                <input type="text" class="form-control" value="{{ $request->ExpiredDate ? date('d/m/Y', strtotime($request->ExpiredDate)) : '-' }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Warehouse From</label>
                            <input type="text" class="form-control" value="{{ $request->WarehouseIDFrom }} - {{ $request->warehouseFrom->WarehouseName ?? '' }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Staff In Charge (From)</label>
                            <input type="text" class="form-control" value="{{ $request->StaffInChargeIDFrom }} - {{ $request->staffFrom->EmployeeName ?? '' }}" readonly>
                        </div>
                    </div>

                    <div class="col-lg-6 col-12 ps-lg-5">
                         <div class="mb-3">
                            <label class="form-label">Need For</label>
                            <textarea class="form-control" rows="1" readonly>{{ $request->NeedFor }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Warehouse To</label>
                            <input type="text" class="form-control" value="{{ $request->WarehouseIDTo }} - {{ $request->warehouseTo->WarehouseName ?? '' }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Staff In Charge (To)</label>
                            <input type="text" class="form-control" value="{{ $request->StaffInChargeIDTo }} - {{ $request->staffTo->EmployeeName ?? '' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                    <h5 class="mb-3">Items</h5>
                </div>

                <div class="table-responsive w-100">
                    <table class="table table-bordered nowrap w-100" style="table-layout: fixed;">
                        <thead>
                        <tr>
                            <th>Part</th>
                            <th>Unit</th>
                            <th>Qty</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($request->details as $detail)
                            <tr>
                                <td>{{ $detail->PartID }} - {{ $detail->part->PartName ?? '' }}</td>
                                <td>{{ $detail->UnitID }} - {{ $detail->unit->UnitName ?? '' }}</td>
                                <td>{{ number_format($detail->Qty, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- END Hero -->

@endsection
