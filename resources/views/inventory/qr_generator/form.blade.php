@php
    $payload = $payload ?? [];
    $code = old('code', $qr->code ?? '');
    $partId = old('part_id', $payload['PartID'] ?? '');
    $unitId = old('unit_id', $payload['UnitID'] ?? '');
    $conversion = $payload['Conversion'] ?? '';
    $batchNo = old('batch_no', $payload['BatchNo'] ?? '');
    $showContent = old('show_content', $qr->show_content ?? 0);
@endphp

<div class="px-lg-5 py-lg-3 p-3">
    <form autocomplete="off" method="post" action="{{ $action }}">
        @csrf
        @if($qr)
            <input type="hidden" name="id" value="{{ $qr->id }}">
        @endif

        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('inventory.qr_generator') }}" class="h3 text-dark mb-0">
                <i class="fa fa-fw fa-arrow-left"></i>
            </a>
            <h1 class="h3 fw-bold ms-4 mb-0">{{ $title }}</h1>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <p class="mb-0">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="block block-rounded">
            <div class="block-content pb-4">
                <button type="submit" class="btn btn-primary mb-4">
                    <i class="fa fa-fw fa-save me-1"></i> Save
                </button>

                <div class="row">
                    <div class="col-lg-3 col-12">
                        <div class="mb-3">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="code" id="code" value="{{ $code }}" maxlength="30" {{ $qr ? 'required' : '' }}>
                                @if(!$qr)
                                    <span class="input-group-text">
                                        <input class="form-check-input mt-0 me-2" type="checkbox" name="automatic" id="automatic" value="1" checked>
                                        Auto
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>


                    <div class="col-lg-3 col-12">
                        <div class="mb-3">
                            <label class="form-label">Part</label>
                            <select class="form-select" name="part_id" id="part_id">
                                @if($partId)
                                    <option value="{{ $partId }}" selected>{{ $partId }}</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-12">
                        <div class="mb-3">
                            <label class="form-label">Unit</label>
                            <select class="form-select" name="unit_id" id="unit_id" @if(!$partId) disabled @endif>
                                @if($unitId)
                                    <option value="{{ $unitId }}" selected>{{ $unitId }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-2 col-12">
                        <div class="mb-3">
                            <label class="form-label">Conversion</label>
                            <input type="text" class="form-control" id="conversion_display" value="{{ $conversion !== '' && $conversion !== null ? $conversion : '' }}" placeholder="-" readonly>
                        </div>
                    </div>
                    <div class="col-lg-2 col-12">
                        <div class="mb-3">
                            <label class="form-label">Batch No</label>
                            <select class="form-select qr-detail-select" name="batch_no" id="batch_no" data-target="batch_no">
                                @if($batchNo)
                                    <option value="{{ $batchNo }}" selected>{{ $batchNo }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Display</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_content" value="1" id="show_content" @checked((bool) $showContent)>
                        <label class="form-check-label" for="show_content">Show QR content on label</label>
                    </div>
                    <div class="form-text">
                        If disabled, the printed label shows only the QR code value. Full values are still stored in the database.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function () {
            const availableStockDetailsUrl = @json(route('helper.available_stock_details'));
            const partSelectUrl = @json(route('misc.part'));
            const partUnitSelectUrl = @json(route('misc.partunit2'));
            const conversionUrl = @json(route('misc.conversion'));
            const detailColumnMap = { batch_no: 'BatchNo' };
            const detailLabelMap = { batch_no: 'Batch No' };

            function hasRequiredFilters() {
                return Boolean($('#part_id').val());
            }

            function selectedDetailFilters() {
                const data = {};
                $('.qr-detail-select').each(function () {
                    if ($(this).val()) {
                        data[$(this).data('target')] = $(this).val();
                    }
                });
                return data;
            }

            function initRemoteSelect(selector, url, placeholder) {
                $(selector).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    allowClear: true,
                    placeholder: placeholder,
                    ajax: {
                        url: url,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { search: params.term || '' };
                        },
                        processResults: function(data) {
                            return { results: data };
                        },
                        cache: true,
                    },
                });
            }

            function initDetailSelect(element) {
                const $element = $(element);
                const target = $element.data('target');

                $element.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    allowClear: true,
                    placeholder: 'Select ' + detailLabelMap[target],
                    ajax: {
                        url: availableStockDetailsUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            const data = {
                                part_id: $('#part_id').val(),
                                target: target,
                                term: params.term || '',
                                filter_qty: 0,
                            };

                            Object.entries(selectedDetailFilters()).forEach(function(entry) {
                                if (entry[0] !== target) {
                                    data[entry[0]] = entry[1];
                                }
                            });

                            return data;
                        },
                        transport: function(params, success, failure) {
                            if (!hasRequiredFilters()) {
                                success({ data: { results: [] } });
                                return null;
                            }

                            const request = $.ajax(params);
                            request.then(success);
                            request.fail(failure);
                            return request;
                        },
                        processResults: function(response) {
                            const column = detailColumnMap[target];
                            const rows = response.data && response.data.results ? response.data.results : [];
                            return {
                                results: rows.map(function(row) {
                                    const value = row[column];
                                    if (value === null || value === '') {
                                        return { id: '__NULL__', text: '(Empty)' };
                                    }
                                    return { id: value, text: value };
                                })
                            };
                        },
                        cache: true,
                    },
                });
            }

            function initUnitSelect() {
                $('#unit_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    allowClear: true,
                    placeholder: 'Select unit',
                    ajax: {
                        url: partUnitSelectUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                id: $('#part_id').val(),
                                search: params.term || '',
                            };
                        },
                        transport: function(params, success, failure) {
                            if (!$('#part_id').val()) {
                                success([]);
                                return null;
                            }

                            const request = $.ajax(params);
                            request.then(success);
                            request.fail(failure);
                            return request;
                        },
                        processResults: function(data) {
                            return { results: data };
                        },
                        cache: true,
                    },
                });
            }

            function resetUnitSelect() {
                $('#unit_id')
                    .val(null)
                    .prop('disabled', !$('#part_id').val())
                    .trigger('change.select2');
            }

            function updateConversionDisplay() {
                const partId = $('#part_id').val();
                const unitId = $('#unit_id').val();
                const batchNo = $('#batch_no').val();

                if (!partId || !unitId) {
                    $('#conversion_display').val('');
                    return;
                }

                $.ajax({
                    url: conversionUrl,
                    data: {
                        part_id: partId,
                        unit_id: unitId,
                        batch_no: batchNo,
                    },
                    dataType: 'json',
                }).done(function(response) {
                    $('#conversion_display').val(response && response.conversion !== undefined && response.conversion !== null ? response.conversion : '');
                }).fail(function() {
                    $('#conversion_display').val('');
                });
            }

            initRemoteSelect('#part_id', partSelectUrl, 'Select part');
            initUnitSelect();
            $('.qr-detail-select').each(function () { initDetailSelect(this); });


            $('#part_id').on('change', function () {
                resetUnitSelect();
                updateConversionDisplay();
                $('.qr-detail-select').val(null).trigger('change');
            });

            $('#unit_id').on('change', function () {
                updateConversionDisplay();
            });

            $('#batch_no').on('change', function () {
                updateConversionDisplay();
            });

            $('#automatic').on('change', function () {
                $('#code').prop('disabled', this.checked).prop('required', !this.checked);
            }).trigger('change');
            updateConversionDisplay();
        });
    </script>
@endsection
