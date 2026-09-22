@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Purchase Return Execute Detail</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('pr_execute') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">
                Purchase Return Execute Detail - {{ $pr->TransactionNo }}
            </h1>
            @if(!$executed && auth()->user()->hasAnyPermission(['admin', 'pr_execute.add']))
                <button class="btn btn-primary ms-auto execute-btn" form="execute-form" type="submit">
                    <i class="fa fa-fw fa-check me-1"></i>Execute
                </button>
            @endif
            @if($executed && auth()->user()->hasAnyPermission(['admin', 'pr_execute.add']))
                <button type="button" id="delete-execution-btn" class="btn btn-danger ms-auto" data-url="{{ route('pr_execute.delete', $pr->id) }}">
                    <i class="fa fa-fw fa-trash me-1"></i>Delete Execution
                </button>
            @endif
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                @if($executed)
                    <span class="badge bg-success">Executed</span>
                @else
                    <span class="badge bg-warning text-dark">Pending</span>
                @endif
            </div>
            @include('partials.admin._transaction_print_form', [
                'transactionNo' => $pr->TransactionNo,
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
                                <td>: {{ $pr->TransactionNo }}</td>
                            </tr>
                            <tr>
                                <th>Transaction Date</th>
                                <td>: {{ \Carbon\Carbon::parse($pr->TransactionDate)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Supplier</th>
                                <td>: {{ $pr->supplier->SupplierName ?? $pr->SupplierID }}</td>
                            </tr>
                            <tr>
                                <th>Reference</th>
                                <td>: {{ $pr->ReceivingNumber ?: '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-lg-6 col-12">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="30%">Division</th>
                                <td>: {{ $pr->division->DivisionName ?? $pr->DivisionID }}</td>
                            </tr>
                            <tr>
                                <th>Currency</th>
                                <td>: {{ $pr->CurrencyID }}</td>
                            </tr>
                            <tr>
                                <th>Grand Total</th>
                                <td>: {{ auto_numeric_format($pr->GrandTotal) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <form id="execute-form" method="post" action="{{ route('pr_execute.execute', $pr->id) }}">
            @csrf
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
                                <th>Warehouse</th>
                                <th>Unit</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Conversion</th>
                                <th class="text-end">Stock Qty</th>
                                <th class="text-end">Unit Price</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($pr->details as $detail)
                                @php($stock = $executedStocks[$detail->Sequence] ?? null)
                                @php($stockQty = $pr->BasedOnGoodsReceiving == 1 ? $detail->Qty : $detail->Qty * $detail->Conversion)
                                <tr>
                                    <td>{{ $detail->part->PartName ?? $detail->PartID }}</td>
                                    <td>{{ $detail->warehouse->WarehouseName ?? $detail->WarehouseID }}</td>
                                    <td>{{ $detail->unit->UnitName ?? $detail->UnitID }}</td>
                                    <td class="text-end">{{ auto_numeric_format($detail->Qty) }}</td>
                                    <td class="text-end">{{ auto_numeric_format($detail->Conversion) }}</td>
                                    <td class="text-end">{{ auto_numeric_format($stockQty) }}</td>
                                    <td class="text-end">{{ auto_numeric_format($detail->UnitPrice) }}</td>
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(function() {
            $('#execute-form').on('submit', function (event) {
                event.preventDefault();
                let form = $(this);

                Swal.fire({
                    icon: 'info',
                    title: 'Execute Purchase Return',
                    text: 'Execute this purchase return?',
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
                    text: 'This reverses the stock movement and journal for this Purchase Return, and allows it to be executed again. Continue?',
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
