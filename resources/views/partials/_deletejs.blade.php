<script>
    $(document).on('click', '.delete-modal', function(){
        $('#modal_delete').modal('show');
        $('#deleted_id').val($(this).data('id'));
    });

    function deleteData(){
        $.ajax({
            type: 'POST',
            url: '{{ route($routeUrl) }}',
            data: {
                '_token': '{{ csrf_token() }}',
                'id': $('#deleted_id').val()
            },
            success: function(data) {
                if ((data.errors)){
                    setTimeout(function () {
                        toastr.error('Gagal menghapus, data sudah terpakai!!', 'Peringatan', {timeOut: 6000, positionClass: "toast-top-center"});
                    }, 500);
                }
                else{
                    window.location = '{{ route($redirectUrl) }}';
                }
            }
        });
    }
</script>
