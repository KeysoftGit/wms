@php
    $routeName = optional(request()->route())->getName();
    $moduleName = $routeName ? explode('.', $routeName)[0] : null;
    $menuName = match ($moduleName) {
        'usage' => 'part_usage',
        'adjust' => 'stock_adj',
        default => $moduleName,
    };
    $printReports = collect();
    $useDynamicReport = \App\Models\ControlPanel::isEnabled('is_dynamic_report');

    if ($useDynamicReport && $menuName) {
        try {
            if (
                \Illuminate\Support\Facades\Schema::hasTable('PrintReport') &&
                \Illuminate\Support\Facades\Schema::hasTable('PrintReportMapping')
            ) {
                $printReports = \Illuminate\Support\Facades\DB::table('PrintReport as reports')
                    ->join('PrintReportMapping as mappings', 'mappings.print_report_id', '=', 'reports.id')
                    ->join('menus', 'menus.id', '=', 'mappings.module_id')
                    ->where('menus.Name', $menuName)
                    ->select('reports.slug', 'reports.title')
                    ->distinct()
                    ->orderBy('reports.title')
                    ->get();
            }
        } catch (\Exception $e) {
            $printReports = collect();
        }
    }

@endphp

@if ($useDynamicReport)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const legacyPrintForm = document.querySelector('form[action*="/print/index"]');
            if (!legacyPrintForm || document.querySelector('[data-print-report-template-form]')) return;

            const transactionInput = legacyPrintForm.querySelector('input[name="transactionCode"]');
            if (!transactionInput || !transactionInput.value) return;

            if (legacyPrintForm.parentElement) {
                legacyPrintForm.parentElement.classList.add(
                    'd-flex', 'flex-row', 'align-items-end', 'gap-2', 'flex-wrap'
                );
            }
            legacyPrintForm.classList.add('d-none');

            const wrapper = document.createElement('form');
            wrapper.setAttribute('autocomplete', 'off');
            wrapper.setAttribute('target', '_blank');
            wrapper.setAttribute('data-print-report-template-form', '1');
            wrapper.method = 'GET';
            wrapper.action = @json(route('print_bridge', [], false));

            const options = @json($printReports);
            const hasOptions = options.length > 0;
            const menuName = @json($menuName);
            const escapeHtml = value => String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            wrapper.innerHTML = `
                <input type="hidden" name="transaction">
                <input type="hidden" name="module">
                <div class="d-flex flex-row align-items-end">
                    <div>
                        <label>Print Type</label>
                        <select class="form-select" name="slug" style="min-width: 160px;" ${hasOptions ? '' : 'disabled'}>
                            ${hasOptions
                                ? options.map(item => `<option value="${escapeHtml(item.slug)}">${escapeHtml(item.title)}</option>`).join('')
                                : '<option value="">No template mapped</option>'}
                        </select>
                    </div>
                    <button type="submit" class="btn btn-alt-success ms-2" ${hasOptions ? '' : 'disabled'}>
                        <i class="fa fa-fw fa-file-pdf me-2"></i>Print
                    </button>
                </div>
            `;
            wrapper.querySelector('input[name="transaction"]').value = transactionInput.value;
            wrapper.querySelector('input[name="module"]').value = menuName;

            legacyPrintForm.insertAdjacentElement('afterend', wrapper);
        });
    </script>
@endif
