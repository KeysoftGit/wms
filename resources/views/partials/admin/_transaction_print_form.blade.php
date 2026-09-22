<form autocomplete="off" action="{{ config('app.report_url') }}/print/index">
    <input type="hidden" name="transactionCode" value="{{ $transactionNo }}">
    <input type="hidden" name="dbGuid" value="{{ session('guid') }}">
    <div class="d-flex flex-row align-items-end">
        <div>
            <label>Print Type</label>
            <select class="form-select" name="code" style="width: 100px;">
                @foreach ($options as $option)
                    <option value="{{ $option->Code }}">{{ $option->Type }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-success ms-2">
            <i class="fa fa-fw fa-print me-2"></i>Print
        </button>
    </div>
</form>
