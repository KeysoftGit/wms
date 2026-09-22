@if(\Illuminate\Support\Facades\Session::has('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
        <p class="mb-0">
            {{ \Illuminate\Support\Facades\Session::get('error') }}
        </p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
