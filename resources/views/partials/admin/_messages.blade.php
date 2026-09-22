@if(\Illuminate\Support\Facades\Session::has('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        <h3 class="alert-heading h4 my-2">Success</h3>
        <p class="mb-0">
            {{ \Illuminate\Support\Facades\Session::get('success') }}
        </p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

@endif

@if(\Illuminate\Support\Facades\Session::has('error'))
    <div class="alert alert-warning alert-dismissible" role="alert">
        <h3 class="alert-heading h4 my-2">Warning</h3>
        <p class="mb-0">
            {{ \Illuminate\Support\Facades\Session::get('error') }}
        </p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(count($errors) > 0)
<div class="alert alert-warning alert-dismissible" role="alert">
    <h3 class="alert-heading h4 my-2">Warning</h3>
    @foreach($errors->all() as $error)
        <p class="mb-0">
            {{ $error }}
        </p>
    @endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif


