@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Menu Access</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('access.store') }}">
                    @csrf

                    <div class="d-flex flex-row align-items-center mb-5">
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Menu Access
                        </h1>
                    </div>

                    @if(count($errors->all()) > 0)
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)
                                <p class="m-0 fs-6">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <input type="hidden" name="permissions[]" v-for="permission in permissions" :value="permission">

                    <div class="block block-rounded">
                        <div class="block-content pb-3">
                            <button type="submit" class="btn btn-primary mb-3 fs-6"><i class="fa fa-fw fa-save me-2"></i>Save</button>

                            <div class="mb-3" style="max-width: 300px;">
                                <label class="form-label">User <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.user') }}" class="form-select"  v-model="user" :prevalue="user"
                                         name="UserID" id="UserID" required>
                                    <option value="">-</option>
                                </select2>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded">
                        <div class="block-content pb-3">
                            <div v-if="isLoading">
                                <h1 class="text-center p-5"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h1>
                            </div>
                            <div v-if="!isLoading && menus.length == 0">
                                <p class="text-center p-5">Pick a user first to edit menu access</p>
                            </div>
                            <div class="row" v-if="!isLoading && menus.length > 0">
                                <div class="col-lg-3 col-12">
                                    <div class="border rounded" style="min-height: 400px; height: calc(100vh - 520px); overflow-y: scroll; overflow-x: hidden">
                                        <p class="m-0 p-2 pointer"
                                           :class="{'bg-primary': selectedMenu == index, 'text-white': selectedMenu == index}"
                                           v-for="(menu, index) in menus" @click="selectMenu(index)">
                                            @{{ menu.name }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-lg-9 col-12">
                                    <div class="border rounded px-3">
                                        <div class="form-check mt-3" v-for="action in menus[selectedMenu].actions">
                                            <input class="form-check-input fs-6" type="checkbox" :value="action.permission" v-model="permissions">
                                            <label class="form-check-label fs-6" for="automatic">
                                                @{{ action.name }}
                                            </label>
                                        </div>
                                        <div class="mb-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </form>

            </div>
        </div>
    </div>
    <!-- END Hero -->

@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-buttons-bs5/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <style>
        .pointer {
            cursor: pointer;
        }
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.5.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-autonumeric@1.2.6/dist/vue-autonumeric.min.js"></script>

    <script type="text/x-template" id="select2-template">
        <select>
            <slot></slot>
        </select>
    </script>
    <script src="{{ asset('js/vueComponent-select2.js') }}"></script>

    <!-- Page JS Code -->
    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {
                isLoading: false,

                user: '{{ old('UserID') ?? '' }}',

                menus: [],
                permissions: [],
                selectedMenu: 0,

                autonumericFormat: {
                    minimumValue: '0',
                    maximumValue: '9999999999999',
                    decimalPlaces: 0,
                    digitGroupSeparator: '.',
                    decimalCharacter: ',',
                    modifyValueOnWheel: false,
                    allowDecimalPadding: false,
                    unformatOnSubmit: true
                },
                autonumericFormat2: {
                    minimumValue: '0',
                    maximumValue: '9999999999999',
                    decimalPlaces: 6,
                    digitGroupSeparator: '.',
                    decimalCharacter: ',',
                    modifyValueOnWheel: false,
                    allowDecimalPadding: false,
                    unformatOnSubmit: true
                },
            },
            methods: {
                getMenus(){
                    this.isLoading = true;

                    let app = this;
                    $.ajax({
                        url: '{{ route('access.menu') }}',
                        type: 'GET',
                        data: {
                            id: app.user,
                        }
                    }).then(function (result){
                        console.log(result);
                        app.menus = result.menus;
                        app.permissions = result.permissions;
                    }).always(function(){
                        app.isLoading = false;
                    });
                },

                selectMenu(index){
                    this.selectedMenu = index;
                }
            },
            computed: {

            },
            watch: {
                user(){
                    this.getMenus();
                },
            },
            mounted() {
                let app = this;


            }
        });

        @if(session()->has('type'))
        One.helpers('jq-notify', {
            type: '{{ session('type') }}',
            icon: '{{ session('icon') }}',
            message: '{{ session('message') }}',
        });
        @endif
    </script>
@endsection
