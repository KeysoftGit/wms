<div v-if="tab == 'warehouse'">
    <!-- Enhanced Filters -->
    <div class="block block-rounded mb-4 shadow-sm border-0" style="background-color: #f8fafc;">
        <div class="block-content pb-3">
            <div class="row align-items-end text-dark">
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <label class="form-label fw-bold" style="color: #1a2542;">Start Period</label>
                    <input type="date" class="form-control border-0 shadow-sm" style="background-color: #fff;" v-model="filter.startDate">
                </div>
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <label class="form-label fw-bold" style="color: #1a2542;">End Period</label>
                    <input type="date" class="form-control border-0 shadow-sm" style="background-color: #fff;" v-model="filter.endDate">
                </div>
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <label class="form-label fw-bold" style="color: #1a2542;">Focus Warehouse</label>
                    <select class="form-select border-0 shadow-sm" style="background-color: #fff;" v-model="filter.warehouse">
                        <option value="">All Locations</option>
                        @php
                            $warehouseOptions = \App\Models\ControlPanel::isEnabled('implement_user_warehouse_mapping')
                                ? \App\Models\MsWarehouse::accessibleTo(auth()->user())
                                : \App\Models\MsWarehouse::query();
                        @endphp
                        @foreach($warehouseOptions->where('Active', 1)->get() as $wh)
                            <option value="{{ $wh->WarehouseID }}">{{ $wh->WarehouseName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <button class="btn btn-primary w-100 mb-2 shadow-sm" style="background-color: #4c78dd; border-color: #4c78dd;" @click="setupWarehouse">
                        <i class="fa fa-sync-alt me-1"></i> Refresh Analysis
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Professional KPI Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
            <div class="block block-rounded h-100 mb-0 shadow-sm border-0" style="background-color: #1a2542;">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-sm fw-semibold text-uppercase mb-1" style="color: #94a3b8;">Inventory Value</div>
                        <div class="fs-3 fw-bold text-white" v-if="loadingWarehouse"><i class="fa fa-spinner fa-spin"></i></div>
                        <div class="fs-3 fw-bold text-white" v-else>@{{ warehouseData.kpis.inventoryValue }}</div>
                    </div>
                    <div class="item item-rounded" style="background-color: rgba(76, 120, 221, 0.2);">
                        <i class="fa fa-coins fs-2" style="color: #4c78dd;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
            <div class="block block-rounded h-100 mb-0 shadow-sm border-0" style="background-color: #1a2542;">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-sm fw-semibold text-uppercase mb-1" style="color: #94a3b8;">Traceable SKUs</div>
                        <div class="fs-2 fw-bold text-white" v-if="loadingWarehouse"><i class="fa fa-spinner fa-spin"></i></div>
                        <div class="fs-2 fw-bold text-white" v-else>@{{ warehouseData.kpis.traceableSkus }}</div>
                    </div>
                    <div class="item item-rounded" style="background-color: rgba(8, 145, 178, 0.2);">
                        <i class="fa fa-barcode fs-2" style="color: #0891b2;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
            <div class="block block-rounded h-100 mb-0 shadow-sm border-0" style="background-color: #1a2542;">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-sm fw-semibold text-uppercase mb-1" style="color: #94a3b8;">Low Stock</div>
                        <div class="fs-2 fw-bold text-danger" v-if="loadingWarehouse"><i class="fa fa-spinner fa-spin"></i></div>
                        <div class="fs-2 fw-bold text-danger" v-else>@{{ warehouseData.kpis.lowStock }}</div>
                    </div>
                    <div class="item item-rounded" style="background-color: rgba(220, 38, 38, 0.2);">
                        <i class="fa fa-arrow-trend-down fs-2 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
            <div class="block block-rounded h-100 mb-0 shadow-sm border-0" style="background-color: #1a2542;">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-sm fw-semibold text-uppercase mb-1" style="color: #94a3b8;">Overstock</div>
                        <div class="fs-2 fw-bold text-warning" v-if="loadingWarehouse"><i class="fa fa-spinner fa-spin"></i></div>
                        <div class="fs-2 fw-bold text-warning" v-else>@{{ warehouseData.kpis.overStock }}</div>
                    </div>
                    <div class="item item-rounded" style="background-color: rgba(234, 88, 12, 0.2);">
                        <i class="fa fa-arrow-trend-up fs-2 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Stock Movement Trend -->
        <div class="col-lg-8 mb-4">
            <div class="block block-rounded shadow-sm h-100 mb-0 border-0 position-relative">
                <div v-if="loadingWarehouse" class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(255,255,255,0.7); z-index: 10; top: 0; left: 0;">
                    <i class="fa fa-2x fa-circle-notch fa-spin text-primary"></i>
                </div>
                <div class="block-header block-header-default" style="background-color: #1a2542;">
                    <h3 class="block-title fw-bold text-white">Stock Movement Dynamics</h3>
                </div>
                <div class="block-content">
                    <div id="wmsMovementTrendChart" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>

        <!-- Valuation by Category -->
        <div class="col-lg-4 mb-4">
            <div class="block block-rounded shadow-sm h-100 mb-0 border-0 position-relative">
                <div v-if="loadingWarehouse" class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(255,255,255,0.7); z-index: 10; top: 0; left: 0;">
                    <i class="fa fa-2x fa-circle-notch fa-spin text-primary"></i>
                </div>
                <div class="block-header block-header-default" style="background-color: #1a2542;">
                    <h3 class="block-title fw-bold text-white">Asset Allocation</h3>
                </div>
                <div class="block-content d-flex align-items-center justify-content-center">
                    <div id="wmsValuationChart" style="min-height: 350px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Fast Moving Items -->
        <div class="col-lg-6 mb-4">
            <div class="block block-rounded shadow-sm h-100 mb-0 border-0 position-relative">
                <div v-if="loadingWarehouse" class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(255,255,255,0.7); z-index: 10; top: 0; left: 0;">
                    <i class="fa fa-2x fa-circle-notch fa-spin text-primary"></i>
                </div>
                <div class="block-header block-header-default" style="background-color: #1a2542;">
                    <h3 class="block-title fw-bold text-white"><i class="fa fa-bolt me-2 text-warning"></i>High Velocity SKUs (Outbound)</h3>
                </div>
                <div class="block-content pb-3">
                    <div id="wmsFastMovingChart" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Critical Stock List -->
        <div class="col-lg-6 mb-4">
            <div class="block block-rounded shadow-sm h-100 mb-0 border-start border-4 border-danger position-relative">
                <div v-if="loadingWarehouse" class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(255,255,255,0.7); z-index: 10; top: 0; left: 0;">
                    <i class="fa fa-2x fa-circle-notch fa-spin text-primary"></i>
                </div>
                <div class="block-header block-header-default" style="background-color: #1a2542;">
                    <h3 class="block-title fw-bold text-white"><i class="fa fa-circle-exclamation me-2 text-danger"></i>Replenishment Alerts</h3>
                </div>
                <div class="block-content p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter mb-0">
                            <thead style="background-color: #f8fafc;">
                                <tr>
                                    <th style="color: #1a2542;">Item Name</th>
                                    <th class="text-center" style="color: #1a2542;">Stok</th>
                                    <th class="text-center" style="color: #1a2542;">Min. Buffer</th>
                                    <th class="text-center" style="color: #1a2542;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="warehouseData.criticalItems.length == 0">
                                    <td colspan="4" class="text-center py-4 text-muted">All stock levels are safe.</td>
                                </tr>
                                <tr v-for="item in warehouseData.criticalItems">
                                    <td class="fw-semibold" style="color: #334155;">@{{ item.PartName }}</td>
                                    <td class="text-center"><span class="badge" style="background-color: #dc2626;">@{{ numberFormat(item.Qty) }}</span></td>
                                    <td class="text-center text-muted">@{{ numberFormat(item.min_qty) }}</td>
                                    <td class="text-center">
                                        <span class="fw-bold" style="color: #dc2626;" v-if="item.Qty <= 0">Out of Stock</span>
                                        <span class="fw-bold" style="color: #ea580c;" v-else>Low Stock</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="block-content block-content-full block-content-sm text-center" style="background-color: #f8fafc;">
                    <a class="fw-bold" style="color: #dc2626;" href="{{ route('monitor') }}">View Full Stock Monitor <i class="fa fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>
