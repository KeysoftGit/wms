
<!-- Fade In Block Modal -->
<div class="modal fade" id="modal-item" tabindex="-1" role="dialog" aria-labelledby="modal-block-fadein" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Produk</h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <div class="row mb-3">
                        <div class="col-sm-12 col-md-6">
                            <div class="dataTables_length">
                                <label>
                                    <select class="form-select form-select-sm" id="take_options" v-on:change="changeTake">
                                        <option>10</option>
                                        <option>20</option>
                                        <option>50</option>
                                        <option>100</option>
                                        <option>200</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="dataTables_filter" style="float: right;">
                                <label>
                                    <input type="search" id="search_text" placeholder="Search.." class="form-control form-control-sm" v-on:input="changeSearch" />
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive w-100">
                                <table class="table table-bordered table-striped table-vcenter js-dataTable-full" id="table_data">
                                    <thead>
                                    <tr>
                                        <th>Id</th>
                                        <th>SKU</th>
                                        <th>Nama</th>
                                        <th>Punya Varian</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-if="itemSelectLoading">
                                        <td colspan="11">Processing...</td>
                                    </tr>
                                    <tr v-if="!itemSelectList.length">
                                        <td colspan="6">No item to show...</td>
                                    </tr>
                                    <tr v-else v-for="item in itemSelectList" v-on:click="chooseItem(item.id, item.description, item.qtyOnHand, item.hasVarian)">
                                        <td>{{ item.id }}</td>
                                        <td>{{ item.sku }}</td>
                                        <td>{{ item.description }}</td>
                                        <td v-if="item.hasVarian" style="color: green;">{{ item.hasVarian }}</td>
                                        <td v-else>{{ item.hasVarian }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="row">
                                    <div class="col-sm-12 col-md-5">
                                        <div class="col-dataTables_info" aria-live="polite" v-if="!itemSelectList.length">Page 0 of 0</div>
                                        <div class="col-dataTables_info" aria-live="polite" v-else>Page {{ activePage }} of {{ totalPage }}</div>
                                    </div>
                                    <div class="col-sm-12 col-md-7">
                                        <div class="dataTables_paginate paging_simple_numbers" style="float: right;">
                                            <ul class="pagination">
                                                <li class="paginate_button page-item previous disabled" v-if="skip === 0">
                                                    <i class="fa fa-angle-left page-link"></i>
                                                </li>
                                                <li class="paginate_button page-item previous" v-else v-on:click="previousPage">
                                                    <i class="fa fa-angle-left page-link"></i>
                                                </li>

                                                <li class="paginate_button page-item next disabled" v-if="activePage === totalPage">
                                                    <i class="fa fa-angle-right page-link"></i>
                                                </li>

                                                <li class="paginate_button page-item next" v-else v-on:click="nextPage">
                                                    <i class="fa fa-angle-right page-link"></i>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body">
                    <button type="button" class="btn btn-sm btn-alt-danger me-1" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END Fade In Block Modal -->



@section('scripts')
    <script>

        let vueComponent2 = new Vue({
            el: "#vue-section",
            data: {
                ph: 'Pilih Produk',
                can_submit: 1,
                qty_error: false,
                branchName: '',
                isLoading: false,
                prDescription: '',
                supplierName: '',
                warehouseName: '',
                warehouseId: 0,
                itemList: [
                    {
                        index: 0,
                        no: 1,
                        itemName: 'Pilih Produk!',
                        itemNameId: 'itemName0',
                        itemValue: 0,
                        itemId: 'item0'
                    }
                ],

                isVarian: false,
                productId: 0,
                selectedItem: '',

                search: '',
                sortField: '',
                sortType: 'ASC',
                skip: 0,
                take: 10,
                totalPage: 0,
                activePage: 1,
                remainingData: 0,
                totalData: 0,
                itemSelectLoading: false,
                itemSelectList: [],
                autonumericFormat: {
                    minimumValue: '0',
                    maximumValue: '9999999999',
                    digitGroupSeparator: '.',
                    decimalCharacter: ',',
                    decimalPlaces: 6,
                    modifyValueOnWheel: false,
                    allowDecimalPadding: true,
                },
            },
            methods: {
                addMoreItem() {
                    let itemIdx = this.itemList.length + 1;
                    let nItem = {
                        index: itemIdx,
                        no: itemIdx++,
                        itemName: 'Pilih Produk!',
                        itemNameId: 'itemName' + itemIdx,
                        itemId: 'item' + itemIdx,
                        itemValue: 0,
                    };

                    this.itemList.push(nItem);
                },
                removeItem(item) {
                    this.itemList = this.itemList.filter(function (x) { return x !== item; });
                },
                checkQty(item, index) {
                    var qty = item.qty;
                    var maxQty = item.maxQty;

                    if (parseInt(qty) > parseInt(maxQty)) {
                        this.qty_error = true;
                        this.can_submit = 0;
                    }
                    else {
                        this.can_submit = 1;
                        this.qty_error = false;
                    }
                },
                checkOnFields() {
                    for (let i = 0; i < this.itemList.length; i++) {
                        if (this.itemList[i].price == 0) {
                            this.can_submit = 0;
                        }
                    }
                },
                loadData() {
                    let url = '@Url.Action("GetProductsForModal", "Product")'
                        + '?skip=' + this.skip + '&take=' + this.take + '&search=' + this.search
                        + '&sort=' + this.sortField + '&sort_type=' + this.sortType;
                    this.itemSelectLoading = true;
                    axios
                        .get(url)
                        .then(response => {
                            this.itemSelectList = response.data.data;
                            this.totalPage = response.data.totalPage;
                            this.activePage = response.data.activePage;
                            this.pages = response.data.pages;
                            this.remainingData = response.data.remainingData;
                            this.totalData = response.data.totalData;
                            this.itemSelectLoading = false;
                        });
                },
                initialLoadData(index) {
                    this.productId = 0;
                    this.isVarian = false;
                    this.selectedItem = index;
                    this.search = '';
                    let url = '@Url.Action("GetProductsForModal", "Product")'
                        + '?skip=' + this.skip + '&take=' + this.take + '&search=' + this.search
                        + '&sort=' + this.sortField + '&sort_type=' + this.sortType;
                    this.itemSelectLoading = true;
                    axios
                        .get(url)
                        .then(response => {
                            this.itemSelectList = response.data.data;
                            this.totalPage = response.data.totalPage;
                            this.activePage = response.data.activePage;
                            this.pages = response.data.pages;
                            this.remainingData = response.data.remainingData;
                            this.totalData = response.data.totalData;
                            this.itemSelectLoading = false;
                        });
                },
                loadDataVariant(productId) {
                    this.productId = productId;
                    this.isVarian = true;
                    let url = '@Url.Action("GetProductsVariantForModal", "Product")'
                        + '?productId=' + productId + '&skip=' + this.skip + '&take=' + this.take + '&search=' + this.search
                        + '&sort=' + this.sortField + '&sort_type=' + this.sortType;
                    this.itemSelectLoading = true;
                    axios
                        .get(url)
                        .then(response => {
                            this.itemSelectList = response.data.data;
                            this.totalPage = response.data.totalPage;
                            this.activePage = response.data.activePage;
                            this.pages = response.data.pages;
                            this.remainingData = response.data.remainingData;
                            this.totalData = response.data.totalData;
                            this.itemSelectLoading = false;
                        });
                },
                changeTake() {
                    this.take = $('#take_options').find(":selected").text();
                    this.skip = 0;
                    if (this.remainingData < this.skip) {
                        this.skip = this.totalData - this.take;
                    }
                    if (this.isVarian) {
                        this.loadDataVariant(this.productId);
                    }
                    else {
                        this.loadData();
                    }
                },
                changeSearch() {
                    this.search = $('#search_text').val();
                    if (this.isVarian) {
                        this.loadDataVariant(this.productId);
                    }
                    else {
                        this.loadData();
                    }
                },
                nextPage() {
                    if (this.activePage !== this.totalPage) {
                        this.skip += parseInt(this.take);
                        if (this.isVarian) {
                            this.loadDataVariant(this.productId);
                        }
                        else {
                            this.loadData();
                        }
                    }
                },
                previousPage() {
                    if (this.skip > 0) {
                        this.skip -= parseInt(this.take);
                        if (this.isVarian) {
                            this.loadDataVariant(this.productId);
                        }
                        else {
                            this.loadData();
                        }
                    }
                },
                changeSort(field, type) {
                    //if 0 then asc
                    //if 1 then desc
                    this.sortField = field;
                    this.sortType = type;
                    if (this.isVarian) {
                        this.loadDataVariant(this.productId);
                    }
                    else {
                        this.loadData();
                    }
                },
                chooseItem(itemValue, itemName, qtyOnHand, hasVariant) {
                    if (hasVariant) {
                        this.itemSelectList = [];
                        this.loadDataVariant(itemValue);
                    }
                    else {
                        this.itemList[this.selectedItem].itemName = itemName;
                        this.itemList[this.selectedItem].itemValue = itemValue;
                        this.itemList[this.selectedItem].qtyOnHand = qtyOnHand;
                        $('#modal-item').modal('toggle');
                    }
                },
            }
        });
    </script>
@endsection
