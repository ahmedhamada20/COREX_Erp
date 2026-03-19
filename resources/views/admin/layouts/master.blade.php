<!doctype html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.layouts.head')
</head>
<body class="theme-1">
@include('admin.layouts.header')
@include('admin.layouts.sidebar')
<div class="page-content-wrapper cairo-font">
    <div class="content-container">
        <div class="page-content">
            @yield('content')
        </div>
    </div>
</div>
@include('admin.layouts.footer')
@include('admin.layouts.footerjs')
</body>
</html>
