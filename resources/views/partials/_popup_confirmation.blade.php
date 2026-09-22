<div id="modal_confirmation" class="modal fade" tabindex="-1" role="dialog">
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
                    <h3 class="block-title">Apakah anda yakin ingin data ini?</h3>
                    <input type="hidden" id="deleted_id" name="deleted_id"/>
                </div>
                <div class="block-content block-content-full text-center bg-body">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Tidak</button>
                    <a id="btn_accept" class="btn btn-success mt-2 text-white" style="cursor: pointer;"
                       v-on:click="submit('{{route()}}', '{{route()}}')">Ya</a>
                    <button type="button" id="btn_delete" class="btn btn-sm btn-primary" onclick="deleteData();">Ya</button>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script>

        //Vue.use(Toasted);
        let vueComponent = new Vue({
            el: '#vue-section',
            data: {
            },
            methods: {
                submit(url, redirectUrl) {
                    var submittedData = [];
                    $(".approvalCheckbox:checked").each(function () {
                        var value = $(this).val();
                        debugger;
                        submittedData.push(value);
                    });
                    debugger;
                    if (submittedData.length > 0) {
                        debugger;
                        axios.post(url, submittedData)
                            .then(response => {
                                console.log(response);
                                window.location.href = redirectUrl;
                            });
                        debugger;
                    }
                    else {
                        $('#close_approve_modal').click();
                    }
                },
            }
        });
    </script>
@endsection