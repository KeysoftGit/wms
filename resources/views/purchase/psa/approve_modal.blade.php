<div class="modal fade" id="approveModal" tabindex="-2" role="dialog" aria-labelledby="modal-block-popout" aria-hidden="true">
    <div class="modal-dialog modal-dialog-popout modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <form autocomplete="off" method="post" action="{{ route('psa.prs_approve') }}">
                    @csrf

                    <input type="hidden" name="id" value="{{ $prs->id }}">

                    <div class="block-header block-header-default">
                        <h3 class="block-title">Approve Purchase Request Settle</h3>
                        <div class="block-options">
                            <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fa fa-fw fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="block-content fs-sm p-3">
                        <label class="form-label">Reasons For Approval</label>
                        <textarea class="form-control" rows="4" name="ReasonsForApprove" required></textarea>
                    </div>
                    <div class="block-content block-content-full text-end bg-body">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
