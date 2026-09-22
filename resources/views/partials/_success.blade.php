@if(\Illuminate\Support\Facades\Session::has('success'))

    <div class="alert alert-success alert-dismissible" role="alert">
        <p class="mb-0">
            {{ \Illuminate\Support\Facades\Session::get('success') }}
        </p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
