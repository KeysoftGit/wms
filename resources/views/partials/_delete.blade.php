<div class="modal fade" id="modal_delete" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-dialog-slideup" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Peringatan</h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm text-center">
                    <h3 class="block-title">Apakah anda yakin ingin hapus data ini?</h3>
                    <input type="hidden" id="deleted_id" name="deleted_id"/>
                </div>
                <div class="block-content block-content-full text-center bg-body">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Tidak</button>
                    <button type="button" id="btn_delete" class="btn btn-sm btn-primary" onclick="deleteData();">Ya</button>
                </div>
            </div>
        </div>
    </div>
</div>
