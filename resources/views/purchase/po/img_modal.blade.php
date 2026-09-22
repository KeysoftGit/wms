<div class="modal fade" id="imgModal" tabindex="-1" role="dialog" aria-labelledby="modal-block-popout" aria-hidden="true">
    <div class="modal-dialog modal-dialog-popout modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Part Image</h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm p-3">
                    <img :src="selectedImg" class="w-100 h-auto rounded">
                </div>
            </div>
        </div>
    </div>
</div>
