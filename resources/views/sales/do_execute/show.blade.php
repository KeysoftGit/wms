@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Delivery Order Execute Detail</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        @php($canExecute = !$executed && !$soClosed && auth()->user()->hasAnyPermission(['admin', 'do_execute.add']))
        @php($canDeleteExecution = $executed && !$soClosedWithReason && auth()->user()->hasAnyPermission(['admin', 'do_execute.add']))

        <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
            <a href="{{ route('do_execute') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <div class="flex-grow-1">
                <h1 class="h3 fw-bold mb-1">Delivery Order Execute</h1>
                <div class="text-muted">{{ $do->TransactionNo }}</div>
            </div>
            @if($canExecute)
                <button class="btn btn-primary execute-btn" form="execute-form" type="submit">
                    <i class="fa fa-fw fa-check me-1"></i>Execute
                </button>
            @endif
            @if($canDeleteExecution)
                <button type="button" id="delete-execution-btn" class="btn btn-danger" data-url="{{ route('do_execute.delete', $do->id) }}">
                    <i class="fa fa-fw fa-trash me-1"></i>Delete Execution
                </button>
            @endif
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            @if($soClosed)
                <span class="badge bg-danger">SO Closed</span>
            @elseif($executed)
                <span class="badge bg-success">Executed</span>
            @else
                <span class="badge bg-warning text-dark fs-6 px-3 py-2">Pending</span>
            @endif
            @include('partials.admin._transaction_print_form', [
                'transactionNo' => $do->TransactionNo,
                'options' => $options,
            ])
        </div>

        @if($soClosed && !$executed)
            <div class="alert alert-warning">
                Sales Order is already closed. This Delivery Order cannot be executed from this screen.
            </div>
        @endif

        <form id="execute-form" method="post" action="{{ route('do_execute.execute', $do->id) }}">
            @csrf
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Header</h3>
            </div>
            <div class="block-content pb-3">
                <div class="row">
                    <div class="col-lg-6 col-12">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="30%">Transaction No</th>
                                <td>: {{ $do->TransactionNo }}</td>
                            </tr>
                            <tr>
                                <th>Transaction Date</th>
                                <td>: {{ \Carbon\Carbon::parse($do->TransactionDate)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Sales Order</th>
                                <td>: {{ $do->ReffNumber ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Customer</th>
                                <td>: {{ $do->reff->customer->CustomerName ?? $do->reff->CustomerID ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-lg-6 col-12">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="30%">Vehicle @if($canExecute)<span class="text-danger">*</span>@endif</th>
                                <td>
                                    @if($canExecute)
                                        <input type="text" id="VehicleID" name="VehicleID" class="form-control form-control-sm" value="{{ $do->VehicleID }}" placeholder="Avanza - B xxxx AC" required>
                                    @else
                                        : {{ $do->VehicleID ?? '-' }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Driver @if($canExecute)<span class="text-danger">*</span>@endif</th>
                                <td>
                                    @if($canExecute)
                                        <input type="text" id="DriverID" name="DriverID" class="form-control form-control-sm" value="{{ $do->DriverID }}" placeholder="Nama lengkap" required>
                                    @else
                                        : {{ $do->DriverID ?? '-' }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>ETA</th>
                                <td>: {{ $do->ETA ? \Carbon\Carbon::parse($do->ETA)->format('d/m/Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td>: {{ $do->ShipmentAddress ?: '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <h5 class="mb-3">Items</h5>

                    <div class="table-responsive w-100">
                        <table class="table table-bordered w-100" style="table-layout: fixed; overflow-x: scroll">
	                            <thead>
	                            <tr>
	                                <th class="text-center" style="width: 300px;">Part</th>
	                                <th class="text-center" style="width: 300px;">Execution Warehouse</th>
	                                <th class="text-center" style="width: 200px;">Batch No</th>
	                                <th class="text-center" style="width: 160px;">Qty 1</th>
	                                <th class="text-center" style="width: 120px;">Unit 1</th>
                                <th class="text-center" style="width: 160px;">Qty 2</th>
                                <th class="text-center" style="width: 120px;">Unit 2</th>
                                <th class="text-center" style="width: 160px;">Executed Qty</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($do->details as $detail)
                                @php($stock = $executedStocks[$detail->Sequence] ?? null)
                                @php($displayMovement = $detailStockMovements[$detail->Sequence] ?? null)
                                @php($executionWarehouse = $detailExecutionWarehouses[$detail->Sequence] ?? null)
                                @php($isServicePart = strtoupper(trim((string) ($detail->part->PartType ?? ''))) === 'N')
                                <tr>
	                                    <td>{{ $detail->PartID }} - {{ $detail->part->PartName ?? '' }}</td>
	                                    <td style="min-width: 240px;">
	                                        @if($isServicePart)
                                                <span class="text-muted">Service</span>
	                                        @elseif($canExecute)
                                                @if($executionWarehouse)
	                                                <input type="hidden" name="warehouse_id[{{ $detail->Sequence }}]" value="{{ $executionWarehouse->WarehouseID }}">
                                                    <div class="form-control form-control-sm bg-light">
                                                        {{ $executionWarehouse->WarehouseID }}{{ $executionWarehouse->WarehouseName ? ' - ' . $executionWarehouse->WarehouseName : '' }}
                                                    </div>
                                                @else
                                                    <div class="form-control form-control-sm bg-light text-danger">
                                                        No stock warehouse found
                                                    </div>
                                                @endif
                                        @else
                                            {{ $stock->WarehouseID ?? $detail->warehouse->WarehouseName ?? $detail->WarehouseID }}
                                        @endif
                                    </td>
                                    <td>{{ $isServicePart ? '-' : ($detail->BatchNo ?: '-') }}</td>
                                    <td class="text-end">{{ auto_numeric_format($displayMovement->Qty ?? $detail->Qty) }}</td>
                                    <td>{{ $displayMovement->UnitName ?? ($detail->unit->UnitName ?? $detail->UnitID) }}</td>
                                    <td class="text-end">{{ auto_numeric_format($displayMovement->Qty2 ?? ($detail->Qty2 ?? 0)) }}</td>
                                    <td>{{ $displayMovement->UnitName2 ?? ($detail->unit2->UnitName ?? ($detail->UnitID2 ?? '-')) }}</td>
                                    <td class="text-end">{{ $isServicePart ? '-' : ($stock ? auto_numeric_format(abs($stock->Qty2 ?? $stock->Qty)) : '-') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(function() {
            $('#execute-form').on('submit', function (event) {
                event.preventDefault();
                let form = $(this);
                let missingFields = [];

                if (!$('#VehicleID').val()) {
                    missingFields.push('Vehicle');
                }

                if (!$('#DriverID').val()) {
                    missingFields.push('Driver');
                }

                if (missingFields.length > 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required Field',
                        text: missingFields.join(' and ') + ' must be filled before execute.'
                    });

                    return;
                }

                Swal.fire({
                    icon: 'info',
                    title: 'Execute Delivery Order',
                    text: 'Execute this delivery order and reduce buku stock?',
                    showDenyButton: true,
                    confirmButtonText: 'Yes',
                    denyButtonText: 'No',
                }).then(function (result) {
                    if (result.isConfirmed) {
                        One.loader('show');
                        $.ajax({
                            url: form.attr('action'),
                            type: 'POST',
                            data: form.serialize(),
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }).done(function (data) {
                            One.loader('hide');
                            One.helpers('jq-notify', {
                                type: 'success',
                                icon: 'fa fa-fw fa-circle-check',
                                message: data.message,
                            });
                            window.location.reload();
                        }).fail(function (xhr) {
                            One.loader('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Execute failed!'
                            });
                        });
                    }
                });
            });

            $('#delete-execution-btn').on('click', function (event) {
                event.preventDefault();
                let url = $(this).data('url');

                Swal.fire({
                    icon: 'warning',
                    title: 'Delete Execution',
                    text: 'This reverses the stock movement and journal for this Delivery Order, and allows it to be executed again. Continue?',
                    showDenyButton: true,
                    confirmButtonText: 'Yes',
                    denyButtonText: 'No',
                }).then(function (result) {
                    if (result.isConfirmed) {
                        One.loader('show');
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }).done(function (data) {
                            One.loader('hide');
                            One.helpers('jq-notify', {
                                type: 'success',
                                icon: 'fa fa-fw fa-circle-check',
                                message: data.message,
                            });
                            window.location.reload();
                        }).fail(function (xhr) {
                            One.loader('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Delete execution failed!'
                            });
                        });
                    }
                });
            });
        });
    </script>
@endsection
