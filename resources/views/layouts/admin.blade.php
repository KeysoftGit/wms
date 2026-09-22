<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

    @yield('titles')
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="description" content="Keysoft ERP Online">
    <meta name="author" content="PT. Infotama Teknologi Indonesia">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph Meta -->
    <meta property="og:title" content="Keysoft ERP Online">
    <meta property="og:site_name" content="Keysoft ERP Online">
    <meta property="og:description" content="Keysoft ERP Online">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="">

    <!-- Icons -->
    <!-- The following icons can be replaced with your own, they are used by desktop and mobile browsers -->
    <link rel="shortcut icon" href="{{ asset('media/favicons/favicon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('media/favicons/favicon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('media/favicons/apple-touch-icon-180x180.png') }}">
    <!-- END Icons -->

    <!-- Stylesheets -->
    <!-- Fonts and OneUI framework -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" id="css-main" href="{{ asset('css/oneui.min.css')}}">
    <link rel="stylesheet" id="css-main" href="{{ asset('css/custom.css')}}">
    <link rel="stylesheet" id="css-main" href="{{ asset('css/themes/keysoft.css')}}">
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">

    <!-- You can include a specific file from css/themes/ folder to alter the default color theme of the template. eg: -->
    <!-- <link rel="stylesheet" id="css-theme" href="assets/css/themes/amethyst.min.css"> -->
    <!-- END Stylesheets -->

    <style>
        body{
            font-size: 0.9rem;
        }
        .table thead th{
            font-size: .9rem;
        }
        .nav-main-link{
            font-size: .9rem;
        }
        #main-content{
            width: 100% !important;
        }
        #page-container.side-scroll #sidebar .content-header, #page-container.side-scroll #sidebar .content-side{
            width: 220px !important;
        }
        .btn{
            font-size: 0.9rem;
        }
        .form-select{
            font-size: 0.9rem;
        }
        .form-control{
            font-size: 0.9rem;
        }
        .col-form-label{
            font-size: 0.9rem;
        }

        .select2-search__field {
            font-size: 0.9rem !important;
        }
        .select2-container--bootstrap-5 .select2-selection {
            font-size: 0.9rem !important;
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            padding-left: 0 !important;
        }
        .select2-results__option {
            font-size: 0.9rem !important;
        }
    </style>

    @yield('styles')

</head>
<body>
<!-- Page Container -->
<div id="page-container" class="sidebar-o sidebar-dark enable-page-overlay side-scroll page-header-fixed main-content-narrow">

    <!-- Sidebar -->
    @include('partials.admin._navigation')
    <!-- END Sidebar -->

    <!-- Header -->
    @include('partials.admin._header')
    <!-- END Header -->

    <!-- Main Container -->
    <main id="main-container">

        <!-- Page Content -->
        @yield('content')
        <!-- END Page Content -->
    </main>
    <!-- END Main Container -->

    <!-- Footer -->
    @include('partials.admin._footer')
    <!-- END Footer -->
</div>
<!-- END Page Container -->

@include('accounting.journal._modal')

<!--
    OneUI JS

    Core libraries and functionality
    webpack is putting everything together at assets/_js/main/app.js
-->
<script src="{{ asset('js/oneui.app.min.js') }}"></script>
<script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('js/form-preserver.js') }}?v={{ filemtime(public_path('js/form-preserver.js')) }}"></script>
<script>
    if (typeof window.scheduleActiveSidebarCentering === 'function') {
        window.scheduleActiveSidebarCentering();
    }

    @if(session('clear_form_preservation'))
        FormPreserver.clear('{{ session('clear_form_preservation') }}');
    @endif
    //One.layout('dark_mode_on');
    //One.helpersOnLoad(['jq-select2']);

    function makeid(length) {
        let result = '';
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        const charactersLength = characters.length;
        let counter = 0;
        while (counter < length) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
            counter += 1;
        }
        return result;
    }
</script>

@include('partials.admin._print_report_integration')
@yield('scripts')

</body>
</html>
