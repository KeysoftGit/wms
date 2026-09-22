@extends('layouts.admin')

@section('titles')
    <title>Keyonline - User Warehouse Mapping</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold mb-0">User Warehouse Mapping</h1>
            @if(auth()->user()->hasAnyPermission(['admin', 'user_warehouse_mapping.add']))
                <a class="btn btn-primary" href="{{ route('user_warehouse_mapping.create') }}">
                    <i class="fa fa-fw fa-plus"></i> Add Mapping
                </a>
            @endif
        </div>

        <div class="alert alert-info">
            Each user has one mapping. Warehouse access can be changed from the edit action.
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Effective Date</th>
                                <th>Warehouses</th>
                                <th>Notes</th>
                                <th>Created By</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mappings as $mapping)
                                @php
                                    $isFuture = $mapping->EffectiveDate->isFuture();
                                @endphp
                                <tr>
                                    <td>{{ $mapping->UserID }}{{ $mapping->UserName ? ' - ' . $mapping->UserName : '' }}</td>
                                    <td>
                                        {{ $mapping->EffectiveDate->format('d/m/Y H:i') }}
                                        @if($isFuture)
                                            <span class="badge bg-warning text-dark">Scheduled</span>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach($details->get($mapping->UserID, collect()) as $detail)
                                            <span class="badge bg-primary me-1 mb-1">
                                                {{ $detail->WarehouseID }}{{ $detail->WarehouseName ? ' - ' . $detail->WarehouseName : '' }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td>{{ $mapping->Notes ?: '-' }}</td>
                                    <td>{{ $mapping->CreatedBy }}</td>
                                    <td class="text-center">
                                        @if(auth()->user()->hasAnyPermission(['admin', 'user_warehouse_mapping.add']))
                                            <a class="btn btn-sm btn-alt-secondary"
                                                href="{{ route('user_warehouse_mapping.edit', $mapping->UserID) }}"
                                                title="Edit warehouses">
                                                <i class="fa fa-fw fa-edit"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">No mapping has been added.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $mappings->links() }}
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.min.js') }}"></script>

    @if(session()->has('type'))
        <script>
            $(function () {
                One.helpers('jq-notify', {
                    type: @json(session('type')),
                    icon: @json(session('icon')),
                    message: @json(session('message'))
                });
            });
        </script>
    @endif
@endsection
