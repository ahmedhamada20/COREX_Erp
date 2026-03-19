<title>@yield('title')</title>
<!-- Meta -->
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="description" content="Dashboard Template Description" />
<meta name="keywords" content="Dashboard Template" />
<meta name="author" content="Techne infosys" />

<!-- Favicon icon -->
<link rel="icon" href="{{asset('dash/assets/images/favicon.svg')}}" type="image/x-icon" />
<link rel="stylesheet" href="{{asset('dash/assets/css/plugins/dataTables.bootstrap5.min.css')}}">
<!-- font css -->
<link rel="stylesheet" href="{{asset('dash/assets/fonts/tabler-icons.min.css')}}">
<link rel="stylesheet" href="{{asset('dash/assets/fonts/feather.css')}}">
<link rel="stylesheet" href="{{asset('dash/assets/fonts/fontawesome.css')}}">
<link rel="stylesheet" href="{{asset('dash/assets/fonts/material.css')}}">

<!-- vendor css -->

<link rel="stylesheet" href="{{asset('dash/assets/css/style-rtl.css')}}" id="main-style-link">
<link rel="stylesheet" href="" id="rtl-style-link">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:slnt,wght@-2,700&display=swap" rel="stylesheet">
<style>
    .cairo-font {
        font-family: "Cairo", sans-serif;
        font-optical-sizing: auto;
        font-weight: 700;
        font-style: normal;
        font-variation-settings: "slnt" -2;
    }
</style>

@yield('css')
