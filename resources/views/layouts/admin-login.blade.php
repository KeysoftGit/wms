<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @yield('title')

    <meta name="description" content="Keysoft ERP Online">
    <meta name="author" content="PT. Infotama Teknologi Indonesia">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph Meta -->
    <meta property="og:title" content="Keysoft ERP Online">
    <meta property="og:site_name" content="PT. Infotama Teknologi Indonesia">
    <meta property="og:description" content="Keysoft ERP Online - Login">
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
    <link rel="stylesheet" id="css-main" href="{{ asset('css/themes/city.min.css')}}">

    <!-- You can include a specific file from css/themes/ folder to alter the default color theme of the template. eg: -->
    <!-- <link rel="stylesheet" id="css-theme" href="assets/css/themes/amethyst.min.css"> -->
    <!-- END Stylesheets -->

    @yield('styles')

    <style>

        /*.form-control{*/
        /*    font-size: 0.75rem;*/
        /*}*/
        /*.btn{*/
        /*    font-size: 0.75rem;*/
        /*}*/
    </style>
</head>
<body>
<!-- Page Container -->
<div id="page-container">

    <!-- Main Container -->
    @yield('content')
    <!-- END Main Container -->
</div>
<!-- END Page Container -->

<script src="{{ asset('js/oneui.app.min.js') }}"></script>

<!-- jQuery (required for jQuery Validation plugin) -->
<script src="{{ asset('js/lib/jquery.min.js') }}"></script>

<!-- Page JS Plugins -->
<script src="{{ asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>

<!-- Page JS Code -->
<script src="{{ asset('js/pages/op_auth_signin.min.js') }}"></script>
</body>
</html>
