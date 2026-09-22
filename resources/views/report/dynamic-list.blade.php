@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Dynamic Report</title>
@endsection

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <style>
        .dynamic-report-header {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
        }

        .dynamic-report-toolbar {
            background: #f5f7fb;
            padding-top: .75rem;
            padding-bottom: .75rem;
        }

        .dynamic-report-table-wrap {
            max-height: calc(100vh - 270px);
            overflow: auto;
        }

        .dynamic-report-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8f9fc;
            border-bottom: 1px solid #e5e7eb;
            color: #64748b;
            font-size: .72rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dynamic-report-table td {
            vertical-align: middle;
        }

        .dynamic-report-description {
            max-width: 520px;
            color: #64748b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dynamic-report-empty {
            display: none;
        }

        #dynamic-report-module + .select2-container {
            width: 100% !important;
        }

        .dynamic-report-search-box .input-group-text {
            background: #fff;
        }
    </style>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="dynamic-report-header p-4 mb-3 shadow-sm">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary">Dynamic Report</span>
                        <span class="badge bg-secondary" id="dynamic-report-count">{{ $reports->count() }} Reports</span>
                    </div>
                    <h1 class="h3 fw-bold mb-1 text-dark">View Dynamic Report</h1>
                    <p class="fs-sm fw-medium text-muted mb-0">Cari report berdasarkan nama, slug, module, atau deskripsi.</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('dashboard') }}" class="btn btn-alt-light">
                        <i class="fa fa-home me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="dynamic-report-toolbar">
            <div class="block block-rounded shadow-sm mb-0 border">
                <div class="block-content p-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-6 col-12 dynamic-report-search-box">
                            <label class="form-label fs-xs fw-bold text-uppercase text-muted mb-1">Search Report</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                                <input type="search" id="dynamic-report-search" class="form-control" placeholder="Search by report name, slug, module, or description" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label fs-xs fw-bold text-uppercase text-muted mb-1">Module</label>
                            <select id="dynamic-report-module" class="form-select form-select2">
                                <option value="">All Modules</option>
                                @foreach ($reports->pluck('ParentModule')->filter()->unique()->sort() as $module)
                                    <option value="{{ $module }}">{{ $modules[$module] ?? $module }}</option>
                                @endforeach
                                @if ($reports->whereNull('ParentModule')->count() > 0 || $reports->where('ParentModule', '')->count() > 0)
                                    <option value="__unassigned">Unassigned</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 col-12">
                            <button type="button" id="dynamic-report-reset" class="btn btn-alt-secondary w-100">
                                <i class="fa fa-rotate-left me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded shadow-sm">
            <div class="block-content p-0">
                <div class="dynamic-report-table-wrap">
                    <table class="table table-vcenter table-hover mb-0 dynamic-report-table">
                        <thead>
                            <tr>
                                <th>Report</th>
                                <th style="width: 180px;">Module</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 190px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="dynamic-report-body">
                            @foreach ($reports as $report)
                                @php
                                    $moduleLabel = $report->ParentModule ? ($modules[$report->ParentModule] ?? $report->ParentModule) : 'Unassigned';
                                    $moduleValue = $report->ParentModule ?: '__unassigned';
                                    $searchText = strtolower(trim($report->ReportName . ' ' . $report->Slug . ' ' . $moduleLabel . ' ' . ($report->Description ?? '')));
                                @endphp
                                <tr class="dynamic-report-row" data-module="{{ $moduleValue }}" data-search="{{ $searchText }}">
                                    <td>
                                        <div class="fw-semibold">{{ $report->ReportName }}</div>
                                        <code class="fs-xs">{{ $report->Slug }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $moduleLabel }}</span>
                                    </td>
                                    <td>
                                        <div class="dynamic-report-description" title="{{ $report->Description }}">
                                            {{ $report->Description ?: '-' }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('report.dynamic.index', $report->Slug) }}" class="btn btn-sm btn-alt-primary">
                                                <i class="fa fa-arrow-right me-1"></i> Open
                                            </a>
                                            <a href="{{ route('report.dynamic.matrix', $report->Slug) }}" class="btn btn-sm btn-alt-secondary">
                                                <i class="fa fa-table-cells-large me-1"></i> Matrix
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @if ($reports->isEmpty())
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-folder-open fa-2x mb-2 d-block text-muted"></i>
                                        No dynamic report found.
                                    </td>
                                </tr>
                            @endif
                            <tr class="dynamic-report-empty">
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fa fa-search fa-2x mb-2 d-block text-muted"></i>
                                    No report matches your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('dynamic-report-search');
            const moduleSelect = document.getElementById('dynamic-report-module');
            const resetButton = document.getElementById('dynamic-report-reset');
            const countBadge = document.getElementById('dynamic-report-count');
            const rows = Array.from(document.querySelectorAll('.dynamic-report-row'));
            const emptyRow = document.querySelector('.dynamic-report-empty');
            const tableWrap = document.querySelector('.dynamic-report-table-wrap');

            if (window.jQuery && jQuery.fn.select2) {
                jQuery(moduleSelect).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'All Modules',
                    allowClear: false,
                    minimumResultsForSearch: 0
                });
            }

            function applyFilter() {
                const keyword = searchInput.value.trim().toLowerCase();
                const module = moduleSelect.value;
                let visibleCount = 0;

                rows.forEach(function (row) {
                    const matchesSearch = keyword === '' || row.dataset.search.includes(keyword);
                    const matchesModule = module === '' || row.dataset.module === module;
                    const isVisible = matchesSearch && matchesModule;

                    row.style.display = isVisible ? '' : 'none';
                    if (isVisible) visibleCount++;
                });

                if (emptyRow) {
                    emptyRow.style.display = rows.length > 0 && visibleCount === 0 ? '' : 'none';
                }

                countBadge.textContent = visibleCount + (visibleCount === 1 ? ' Report' : ' Reports');
                if (tableWrap) {
                    tableWrap.style.minHeight = visibleCount > 0 ? 'auto' : '180px';
                }
            }

            searchInput.addEventListener('input', applyFilter);
            moduleSelect.addEventListener('change', applyFilter);
            resetButton.addEventListener('click', function () {
                searchInput.value = '';
                moduleSelect.value = '';
                if (window.jQuery && jQuery.fn.select2) {
                    jQuery(moduleSelect).trigger('change');
                } else {
                    applyFilter();
                }
                searchInput.focus();
            });
        });
    </script>
@endsection
