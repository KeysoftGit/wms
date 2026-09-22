<div class="modal fade" id="warehouseModal" tabindex="-1" role="dialog" aria-labelledby="modal-block-popout" aria-hidden="true">
    <div class="modal-dialog modal-dialog-popout modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">@{{ selectedPart }}</h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm p-3">
                    <div v-if="isLoading">
                        <h3 class="m-0 p-5 text-center"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h3>
                    </div>
                    <div class="table-responsive" v-if="!isLoading">
                        <table class="table table-striped warehouse-table">
                            <thead>
                            <tr>
                                <th class="text-center">Warehouse ID</th>
                                <th class="text-center">Warehouse Name</th>
                                <th class="text-center">Stock</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(item, index) in details">
                                <td class="text-center">@{{ item.id }}</td>
                                <td class="text-center">@{{ item.name }}</td>
                                <td class="text-center">@{{ item.qty }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
