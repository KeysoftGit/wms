<div v-if="tab == 'main'">
    <div class="row mb-3">
        <div class="col-xxl-3 col-lg-6 col-12 h-auto mb-xxl-0 mb-3">
            <div class="kpi-card h-100 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center mb-2">
                    <div class="kpi-icon me-3">
                        <i class="fa fa-chart-line text-white"></i>
                    </div>
                    <div class="kpi-label">GROSS PROFIT MARGIN</div>
                </div>
                <div class="kpi-value" v-if="loadingMain1"><i class="fa fa-fw fa-spin fa-circle-notch"></i></div>
                <div class="kpi-value" v-else>@{{ numberFormat(grossP) }}%</div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-6 col-12 h-auto mb-xxl-0 mb-3">
            <div class="kpi-card h-100 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center mb-2">
                    <div class="kpi-icon me-3">
                        <i class="fa fa-calculator text-white"></i>
                    </div>
                    <div class="kpi-label">OPEX RATIO</div>
                </div>
                <div class="kpi-value" v-if="loadingMain1"><i class="fa fa-fw fa-spin fa-circle-notch"></i></div>
                <div class="kpi-value" v-else>@{{ numberFormat(opexP) }}%</div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-6 col-12 h-auto mb-xxl-0 mb-3">
            <div class="kpi-card h-100 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center mb-2">
                    <div class="kpi-icon me-3">
                        <i class="fa fa-percent text-white"></i>
                    </div>
                    <div class="kpi-label">EBIT MARGIN</div>
                </div>
                <div class="kpi-value" v-if="loadingMain1"><i class="fa fa-fw fa-spin fa-circle-notch"></i></div>
                <div class="kpi-value" v-else>@{{ numberFormat(ebitP) }}%</div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-6 col-12 h-auto">
            <div class="kpi-card h-100 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center mb-2">
                    <div class="kpi-icon me-3">
                        <i class="fa fa-wallet text-white"></i>
                    </div>
                    <div class="kpi-label">NET PROFIT MARGIN</div>
                </div>
                <div class="kpi-value" v-if="loadingMain1"><i class="fa fa-fw fa-spin fa-circle-notch"></i></div>
                <div class="kpi-value" v-else>@{{ numberFormat(netProfitM) }}%</div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-8 col-12 h-auto">
            <div class="block block-rounded mb-3">
                <div class="block-content pb-3">
                    <h3>Revenue</h3>
                    <div style="height: 250px" class="d-flex flex-row justify-content-center align-items-center">
                        <h1 class="text-center fw-bold m-0" v-if="loadingMain2"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                        <div id="revenue" class="w-100" v-else></div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded mb-3">
                <div class="block-content pb-3">
                    <h3>Operational Expense</h3>
                    <div style="height: 250px" class="d-flex flex-row justify-content-center align-items-center">
                        <h1 class="text-center fw-bold m-0" v-if="loadingMain3"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                        <div id="expense" class="w-100" v-else></div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded m-0">
                <div class="block-content pb-3">
                    <h3>Earning Before Interest and Taxes</h3>
                    <div style="height: 250px" class="d-flex flex-row justify-content-center align-items-center">
                        <h1 class="text-center fw-bold m-0" v-if="loadingMain4"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                        <div id="ebit" class="w-100" v-else></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-12 h-auto">
            <div class="block block-rounded h-100">
                <div class="block-content d-flex flex-column pb-3">
                    <h5>Income Statement</h5>

                    <div class="h-auto d-flex flex-row justify-content-center align-items-center" v-if="loadingMain5">
                        <h1 class="text-center fw-bold m-0"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                    </div>

                    <table class="table table-borderless" v-else>
                        <tr>
                            <td>Revenue</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(revenue) }}</td>
                        </tr>
                        <tr>
                            <td>COGS</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(cogs) }}</td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="w-100 border-top"></div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>OPEX</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(opex) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">General & Admin</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(general) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Marketing</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(marketing) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">IT</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(it) }}</td>
                        </tr>
                        <tr>
                            <td>OTHER INCOME</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(otherIncome) }}</td>
                        </tr>
                        <tr>
                            <td>OTHER EXPENSE</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(otherExpense) }}</td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="w-100 border-top"></div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>EBIT</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(ebit) }}</td>
                        </tr>
                        <tr>
                            <td>Interest and Tax</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(interestTax) }}</td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="w-100 border-top"></div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>NET PROFIT</td>
                            <td class="text-end ps-2"><span style="float: left">Rp.</span> @{{ numberFormat(netProfit) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



