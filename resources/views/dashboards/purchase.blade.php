<div v-if="tab == 'purchase'">
    <div class="block block-rounded mb-3">
        <div class="block-content pb-3">
            <div class="row">
                <div class="col-lg-5 col-12 h-auto">
                    <div class="row">
                        <div class="col-lg-4 col-12">
                            <div style="height: 250px" class="d-flex flex-row justify-content-center align-items-center">
                                <h1 class="text-center fw-bold m-0" v-if="loadingPurchase1"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                                <div id="supplierChart" v-else></div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-12">
                            <div style="height: 250px" class="d-flex flex-row justify-content-center align-items-center">
                                <h1 class="text-center fw-bold m-0" v-if="loadingPurchase2"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                                <div id="activeSupplierChart" v-else></div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-12">
                            <div style="height: 250px" class="d-flex flex-row justify-content-center align-items-center">
                                <h1 class="text-center fw-bold m-0" v-if="loadingPurchase3"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                                <div id="nonactiveSupplierChart" v-else></div>
                            </div>
                        </div>
                    </div>
                    <p class="m-0 text-info">*Active Suppliers means they have transaction from the last 6 months</p>
                </div>

                <div class="col-lg-3 col-12 h-auto">
                    <div class="h-100 d-flex flex-row justify-content-center align-items-center" v-if="loadingPurchase4">
                        <h1 class="text-center fw-bold m-0"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                    </div>
                    <div class="h-100 border-start border-end p-3 d-flex flex-column justify-content-between" v-else>
                        <div class="rounded p-3 mb-2" style="background-color: #f5f2f2">
                            <h4 class="m-0"><i class="fa fa-fw fa-certificate" style="color: #edce00"></i> <span class="mx-3">@{{ numberFormat(goldPartner) }}</span> Gold Partner</h4>
                        </div>
                        <div class="rounded p-3 mb-2" style="background-color: #f5f2f2">
                            <h4 class="m-0"><i class="fa fa-fw fa-certificate" style="color: #b3b3b3"></i> <span class="mx-3">@{{ numberFormat(silverPartner) }}</span> Silver Partner</h4>
                        </div>
                        <div class="rounded p-3" style="background-color: #f5f2f2">
                            <h4 class="m-0"><i class="fa fa-fw fa-certificate" style="color: #8f5635"></i> <span class="mx-3">@{{ numberFormat(bronzePartner) }}</span> Bronze Partner</h4>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-12 h-auto">
                    <div class="h-100 d-flex flex-row justify-content-center align-items-center" v-if="loadingPurchase5">
                        <h1 class="text-center fw-bold m-0"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                    </div>
                    <div class="h-100 py-3 d-flex flex-column justify-content-between" v-else>
                        <div class="rounded p-3 mb-2" style="background-color: #f5f2f2">
                            <p class="mb-1 fs-6">Total Spending</p>
                            <h3 class="m-0">Rp. @{{ numberFormat(spending) }}</h3>
                        </div>
                        <div class="rounded p-3" style="background-color: #f5f2f2">
                            <p class="mb-1 fs-6">Savings</p>
                            <h3 class="m-0">Rp. @{{ numberFormat(saving) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-12 h-auto">
            <div class="block block-rounded h-100">
                <div class="block-content pb-3">
                    <h3>Procurement By Category In Current Year</h3>

                    <h1 class="text-center fw-bold m-0" v-if="loadingPurchase6"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                    <table class="table table-borderless" v-else>
                        <tr>
                            <th style="width: 20%">Category</th>
                            <th style="width: 15%">Supplier(s)</th>
                            <th>Procurement (%)</th>
                        </tr>

                        <tr style="vertical-align: middle" v-for="item in procurement">
                            <td>@{{ item.name }}</td>
                            <td>@{{ item.supplier }}</td>
                            <td>
                                <div class="w-100" style="background-color: #f5f2f2">
                                    <div :style="{ width: item.percentage+'%'} " class="py-2 bg-primary">
                                        <p class="m-0 text-center fw-bold text-white">@{{ numberFormat(item.percentage) }}%</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12 h-auto">
            <div class="block block-rounded mb-3">
                <div class="block-content pb-3">
                    <h3>Average Procurement Cycle Time (InDays)</h3>

                    <div class="position-relative">
                        <div style="position: absolute; top: 30px; left: 0; right: 0; height: 10px; background-color: #f5f2f2; z-index: 0" class="mx-3">

                        </div>
                        <div class="d-flex flex-row justify-content-between align-items-center pb-4 px-3 position-relative" style="z-index: 1">
                            <div class="rounded-full p-2" style="background-color: #f5f2f2">
                                <div class="rounded-full bg-success" style="width: 15px; height: 15px"></div>
                            </div>

                            <div class="rounded-full p-4 d-flex flex-row justify-content-center align-items-center" style="background-color: #f5f2f2" style="width: 50px; height: 50px">
                                <p class="m-0">@{{ numberFormat(order) }} d</p>
                            </div>

                            <div class="rounded-full p-2" style="background-color: #f5f2f2">
                                <div class="rounded-full bg-success" style="width: 15px; height: 15px"></div>
                            </div>

                            <div class="rounded-full p-4 d-flex flex-row justify-content-center align-items-center" style="background-color: #f5f2f2" style="width: 50px; height: 50px">
                                <p class="m-0">@{{ numberFormat(delivery) }} d</p>
                            </div>

                            <div class="rounded-full p-2" style="background-color: #f5f2f2">
                                <div class="rounded-full bg-success" style="width: 15px; height: 15px"></div>
                            </div>
                        </div>
                        <div class="d-flex flex-row justify-content-between align-items-center" style="position: absolute; bottom: 0; left: 0; right: 0">
                            <p class="m-0 text-center">Order<br>Placement</p>
                            <p class="m-0 text-center">Delivery</p>
                            <p class="m-0 text-center">Invoicing</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded h-auto">
                <div class="block-content pb-3">
                    <h3>Average Procurement Cycle (Supplier Classification)</h3>

                    <div style="height: 350px" class="d-flex flex-row justify-content-center align-items-center">
                        <h1 class="text-center fw-bold m-0" v-if="loadingPurchase8"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                        <div id="avgSupplier" class="w-100" v-else></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
