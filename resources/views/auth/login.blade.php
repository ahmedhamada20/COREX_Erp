<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>تسجيل الدخول</title>

    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('dash/assets/images/favicon.svg') }}" type="image/x-icon"/>

    <!-- Icons / Fonts -->
    <link rel="stylesheet" href="{{ asset('dash/assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dash/assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('dash/assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('dash/assets/fonts/material.css') }}">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('dash/assets/css/style-rtl.css') }}" id="main-style-link">
    <style>
        /* Eye icon */
        .password-toggle {
            position: absolute;
            top: 50%;
            left: 16px;          /* RTL */
            transform: translateY(-50%);
            cursor: pointer;
            color: #667085;
            font-size: 20px;
        }

        .password-toggle:hover {
            color: #6366f1;
        }

    </style>
</head>

<body>

<div class="m-0 authentication-inner row">

    <!-- Image side -->
    <div class="p-0 d-none d-lg-block col-lg-7 col-xl-8 img-side">
        <img
            class="img-fluid w-100"
            src="{{ asset('dash/assets/images/auth/using-laptop-gray-wall.jpg') }}"
            alt="تسجيل الدخول"
        >
    </div>

    <!-- Login form -->
    <div class="p-4 d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg p-sm-5">
        <form class="form w-100" method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mx-auto w-px-200">
                <h4 class="mb-2">أهلاً وسهلاً بك 👋</h4>
                <p class="mb-4">سجّل الدخول للمتابعة</p>

                {{-- Errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="row justify-content-center">
                    <div class="col-lg-12">

                        <div class="mb-3 form-group">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                autocomplete="off"
                                placeholder="example@email.com"
                                value="{{ old('email') }}"
                                required
                            >
                        </div>

                        <div class="mb-3 form-group">
                            <label class="form-label">كلمة المرور</label>

                            <div class="position-relative">
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-control pe-5"
                                    placeholder="••••••••"
                                    required
                                >

                                <span
                                    class="password-toggle"
                                    onclick="togglePassword()"
                                >
                                <i id="eyeIcon" class="ti ti-eye"></i>
                            </span>
                            </div>
                        </div>


                        <div class="mb-4 d-grid">
                            <button type="submit" class="mt-2 btn btn-primary btn-block">
                                تسجيل الدخول
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JS -->
<script src="{{ asset('dash/assets/js/vendor-all.js') }}"></script>
<script src="{{ asset('dash/assets/js/plugins/bootstrap.min.js') }}"></script>
<script src="{{ asset('dash/assets/js/plugins/feather.min.js') }}"></script>
<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('ti-eye');
            eyeIcon.classList.add('ti-eye-off');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('ti-eye-off');
            eyeIcon.classList.add('ti-eye');
        }
    }
</script>

<script>
    if (window.feather) feather.replace();
</script>

</body>
</html>
