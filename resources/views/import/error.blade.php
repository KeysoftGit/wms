<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-labelledby="error-modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-popout modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Importing Failed</h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content">
                    <p>The sheet you're trying to upload contains problems.<br>See the detail below : </p>

                    <div class="alert alert-danger" style="max-height: 50vh; overflow-y: auto;">
                        <ul class="mb-0 ps-3">
                            <template v-if="errors && errors.length > 0">
                                <li v-for="(error, index) in errors" :key="index" class="mb-1">
                                    @{{ error }}
                                </li>
                            </template>
                            <template v-else>
                                <li>No error details available</li>
                            </template>
                        </ul>
                    </div>

                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
