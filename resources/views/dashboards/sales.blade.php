<div v-if="tab == 'sales'">
    <div class="row mb-3">
        <div class="col-xxl-3 col-lg-6 col-12 mb-xxl-0 mb-3">
            <div class="kpi-card h-100 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center mb-2">
                    <div class="kpi-icon me-3">
                        <i class="fa fa-shopping-cart text-white"></i>
                    </div>
                    <div class="kpi-label text-white">NUMBER OF SALES</div>
                </div>
                <div class="kpi-value text-white" v-if="loadingSales1"><i class="fa fa-fw fa-spin fa-circle-notch"></i></div>
                <div class="kpi-value text-white" v-else>@{{ numberFormat(totalSales) }}</div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-6 col-12 mb-xxl-0 mb-3">
            <div class="kpi-card h-100 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center mb-2">
                    <div class="kpi-icon me-3">
                        <i class="fa fa-dollar-sign text-white"></i>
                    </div>
                    <div class="kpi-label text-white">REVENUE</div>
                </div>
                <div class="kpi-value text-white" v-if="loadingSales1"><i class="fa fa-fw fa-spin fa-circle-notch"></i></div>
                <div class="kpi-value text-white" v-else>Rp. @{{ numberFormat(revenue) }}</div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-6 col-12 mb-xxl-0 mb-3">
            <div class="kpi-card h-100 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center mb-2">
                    <div class="kpi-icon me-3">
                        <i class="fa fa-coins text-white"></i>
                    </div>
                    <div class="kpi-label text-white">PROFIT</div>
                </div>
                <div class="kpi-value text-white" v-if="loadingSales1"><i class="fa fa-fw fa-spin fa-circle-notch"></i></div>
                <div class="kpi-value text-white" v-else>Rp. @{{ numberFormat(profit) }}</div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-6 col-12">
            <div class="kpi-card h-100 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center mb-2">
                    <div class="kpi-icon me-3">
                        <i class="fa fa-tags text-white"></i>
                    </div>
                    <div class="kpi-label text-white">COST</div>
                </div>
                <div class="kpi-value text-white" v-if="loadingSales1"><i class="fa fa-fw fa-spin fa-circle-notch"></i></div>
                <div class="kpi-value text-white" v-else>Rp. @{{ numberFormat(cost) }}</div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-8 col-12 h-auto">
            <div class="block block-rounded mb-3">
                <div class="block-content pb-3">
                    <h3>SALES REVENUE</h3>

                    <div style="height: 300px" class="d-flex flex-row justify-content-center align-items-center">
                        <h1 class="text-center fw-bold m-0" v-if="loadingSales2"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                        <div id="salesRevenue" class="w-100" v-else></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5 col-12 h-auto">
                    <div class="block block-rounded h-100">
                        <div class="block-content pb-3">
                            <div class="h-auto d-flex flex-row justify-content-center align-items-center" v-if="loadingSales3">
                                <h1 class="text-center fw-bold m-0"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                            </div>

                            <table class="table table-borderless" v-else>
                                <tr>
                                    <td colspan="2" class="fs-3 fw-bold">UP/CROSS SELL</td>
                                </tr>
                                <tr>
                                    <td>Revenue</td>
                                    <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(upRevenue) }}</td>
                                </tr>
                                <tr>
                                    <td>% Of Revenue</td>
                                    <td class="text-end">@{{ numberFormat(upRevenueP) }}%</td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="w-100 border-top"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="fs-3 fw-bold">DISCONTINUED CUSTOMER</td>
                                </tr>
                                <tr>
                                    <td>Total</td>
                                    <td class="text-end">@{{ numberFormat(churnTotal) }}</td>
                                </tr>
                                <tr>
                                    <td>Rate</td>
                                    <td class="text-end">@{{ numberFormat(churnRate) }}%</td>
                                </tr>
                                <tr>
                                    <td>Revenue</td>
                                    <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(churnRevenue) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 col-12 h-auto">
                    <div class="block block-rounded h-100">
                        <div class="block-content pb-3">
                            <h3>SALES BY SUBDISTRICT</h3>

                            <div style="height: 350px" class="d-flex flex-row justify-content-center align-items-center">
                                <h1 class="text-center fw-bold m-0" v-if="loadingSales4"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                                <div id="accRevenue" class="w-100" v-else></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-12 h-auto">
            <div class="block block-rounded mb-3">
                <div class="block-content pb-3">
                    <h3>REVENUE BASED ON CATEGORY</h3>

                    <div style="height: 320px" class="d-flex flex-row justify-content-center align-items-center">
                        <h1 class="text-center fw-bold m-0" v-if="loadingSales5"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                        <div id="costBreakdown" class="w-100" v-else></div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content pb-3">
                    <h3>SALES BY DIVISION</h3>

                    <div style="height: 350px" class="d-flex flex-row justify-content-center align-items-center">
                        <h1 class="text-center fw-bold m-0" v-if="loadingSales6"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                        <div id="incremental" class="w-100" v-else></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
