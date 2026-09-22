@extends('layouts.admin-login')

@section('title')
    <title>Keysoft NLA - Secure Login</title>
@endsection

@section('content')
    <style>
        body,
        html {
            margin: 0 !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
            font-family: 'Inter', system-ui, sans-serif !important;
            overflow-x: hidden !important;
        }

        * {
            box-sizing: border-box !important;
        }

        #main-container {
            min-height: 100vh !important;
            width: 100% !important;
            display: block !important;
        }

        .hero-static {
            display: grid !important;
            grid-template-columns: 1.45fr 1fr !important;
            min-height: 100vh !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            position: relative !important;
        }

        .left-hero {
            background:
                radial-gradient(circle at 25% 35%, rgba(225, 29, 72, .22), transparent 55%),
                linear-gradient(135deg, #0f1f3a, #12234c) !important;
            color: #fff !important;
            padding: 80px 70px !important;
            margin: 0 !important;
            display: flex !important;
            align-items: center !important;
            position: relative !important;
            z-index: 1 !important;
            overflow: hidden !important;
        }

        .left-inner {
            max-width: 520px !important;
            width: 100% !important;
            position: relative !important;
            z-index: 2 !important;
        }

        .hero-logo {
            width: 200px !important;
            height: auto !important;
            margin-bottom: 20px !important;
            display: block !important;
            position: relative !important;
            z-index: 3 !important;
        }

        .left-inner h1 {
            font-size: 32px !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
            margin-bottom: 20px !important;
            color: #fff !important;
        }

        .left-inner p {
            font-size: 16px !important;
            line-height: 1.75 !important;
            color: rgba(255, 255, 255, .75) !important;
            margin-bottom: 30px !important;
        }

        .hero-divider {
            width: 60px !important;
            height: 3px !important;
            background: #e11d48 !important;
            margin: 30px 0 35px 0 !important;
            border-radius: 1px !important;
            display: block !important;
        }

        .hero-points div {
            font-size: 15px !important;
            margin-bottom: 14px !important;
            color: rgba(255, 255, 255, .85) !important;
            display: flex !important;
            align-items: center !important;
        }

        .hero-points i {
            color: #e11d48 !important;
            margin-right: 10px !important;
            width: 20px !important;
            text-align: center !important;
        }

        .hero-footer {
            display: flex !important;
            gap: 60px !important;
            margin-top: 50px !important;
        }

        .hero-footer strong {
            font-size: 28px !important;
            display: block !important;
            color: #fff !important;
        }

        .hero-footer span {
            font-size: 13px !important;
            color: rgba(255, 255, 255, .6) !important;
            display: block !important;
            margin-top: 4px !important;
        }

        .right-panel {
            background: #f7f9fc !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            position: relative !important;
            z-index: 1 !important;
            margin: 0 !important;
        }

        .login-card {
            width: 100% !important;
            max-width: 420px !important;
            background: #ffffff !important;
            padding: 46px 42px !important;
            border-radius: 5px !important;
            box-shadow:
                0 30px 60px rgba(0, 0, 0, .15),
                0 8px 20px rgba(0, 0, 0, .08) !important;
            animation: fadeUp .6s ease forwards !important;
            position: relative !important;
            z-index: 2 !important;
        }

        .login-card .text-center {
            text-align: center !important;
            margin-bottom: 30px !important;
        }

        .login-card img {
            width: 180px !important;
            height: auto !important;
            margin: 0 auto 20px auto !important;
            display: block !important;
        }

        .input-group {
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            margin-bottom: 16px !important;
        }

        .input-group .form-control {
            padding-left: 45px !important;
            padding-right: 45px !important;
            flex: 1 !important;
            margin-bottom: 0 !important;
            position: relative !important;
            z-index: 2 !important;
        }

        .input-icon-left {
            position: absolute !important;
            left: 16px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: #94a3b8 !important;
            font-size: 16px !important;
            z-index: 3 !important;
            pointer-events: none !important;
            background: #ffffff !important;
            padding: 0 5px !important;
            transition: color 0.2s ease !important;
        }

        .input-group .form-control:focus~.input-icon-left {
            color: #e11d48 !important;
        }

        .password-toggle {
            position: absolute !important;
            right: 16px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            background: none !important;
            border: none !important;
            color: #94a3b8 !important;
            font-size: 16px !important;
            cursor: pointer !important;
            z-index: 3 !important;
            padding: 0 !important;
            width: 24px !important;
            height: 24px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: color 0.2s !important;
        }

        .password-toggle:hover {
            color: #64748b !important;
        }

        .form-control,
        .form-control-lg,
        .form-control-alt {
            border-radius: 3px !important;
            border: 1px solid #e2e8f0 !important;
            padding: 14px 16px !important;
            font-size: 15px !important;
            width: 100% !important;
            display: block !important;
            margin-bottom: 16px !important;
            background: #fff !important;
            color: #333 !important;
            transition: border-color 0.3s, box-shadow 0.3s !important;
        }

        .form-control-alt:focus {
            border-color: #e11d48 !important;
            box-shadow: 0 0 0 3px rgba(225, 29, 72, .15) !important;
            outline: none !important;
        }

        .mb-3,
        .mb-4 {
            margin-bottom: 20px !important;
            display: block !important;
            width: 100% !important;
        }

        /* ===============================
                   BUTTON
                ================================ */
        .btn,
        .btn-login {
            background: linear-gradient(135deg, #e11d48, #e11d48) !important;
            border-radius: 3px !important;
            padding: 16px !important;
            font-weight: 600 !important;
            color: #fff !important;
            border: none !important;
            transition: .2s !important;
            width: 100% !important;
            display: block !important;
            text-align: center !important;
            cursor: pointer !important;
            font-size: 16px !important;
            text-decoration: none !important;
        }

        .btn-login:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 30px rgba(225, 29, 72, .35) !important;
        }

        .btn i {
            margin-right: 8px !important;
        }

        /* ===============================
                   ERROR MESSAGES
                ================================ */
        .error-text {
            font-size: 13px !important;
            color: #e11d48 !important;
            margin-bottom: 12px !important;
            padding: 8px 12px !important;
            background: rgba(225, 29, 72, .08) !important;
            border-radius: 2px !important;
            border-left: 3px solid #e11d48 !important;
            display: block !important;
        }

        /* ===============================
                   FOOTER
                ================================ */
        .login-footer {
            text-align: center !important;
            font-size: 12px !important;
            color: #94a3b8 !important;
            margin-top: 30px !important;
            padding-top: 20px !important;
            border-top: 1px solid #e2e8f0 !important;
            display: block !important;
        }

        /* ===============================
                   ANIMATION
                ================================ */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===============================
                   RESPONSIVE
                ================================ */
        @media (max-width: 1200px) {
            .left-hero {
                padding: 60px 50px !important;
            }

            .left-inner h1 {
                font-size: 38px !important;
            }

            .hero-logo {
                width: 50px !important;
                margin-bottom: 40px !important;
            }
        }

        @media (max-width: 991px) {
            .hero-static {
                grid-template-columns: 1fr !important;
                display: flex !important;
                flex-direction: column !important;
            }

            .left-hero {
                display: none !important;
            }

            .right-panel {
                width: 100% !important;
                min-height: 100vh !important;
                padding: 40px 20px !important;
            }

            .login-card {
                padding: 36px 32px !important;
            }
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 32px 24px !important;
                margin: 0 10px !important;
            }

            .left-inner h1 {
                font-size: 32px !important;
            }

            .hero-footer {
                gap: 40px !important;
            }

            .hero-footer strong {
                font-size: 24px !important;
            }

            .input-group .form-control {
                padding-left: 40px !important;
                padding-right: 40px !important;
            }

            .input-icon-left {
                left: 12px !important;
            }

            .password-toggle {
                right: 12px !important;
            }
        }

        /* ===============================
                   UTILITY CLASSES
                ================================ */
        .w-100 {
            width: 100% !important;
        }

        .text-center {
            text-align: center !important;
        }

        .mb-2 {
            margin-bottom: 20px !important;
        }

        .mb-4 {
            margin-bottom: 24px !important;
        }

        .me-1 {
            margin-right: 8px !important;
        }

        .opacity-50 {
            opacity: 0.5 !important;
        }

        /* ===============================
            INPUT GROUP IMPROVEMENT
          ================================ */

        .input-group {
            position: relative !important;
            margin-bottom: 18px !important;
        }

        .input-group .form-control {
            padding-left: 52px !important;
            padding-right: 52px !important;
            border-radius: 6px !important;
        }

        .input-icon-left {
            left: 18px !important;
            font-size: 15px !important;
        }

        .password-toggle {
            right: 18px !important;
        }

        .input-group:focus-within .form-control {
            border-color: #e11d48 !important;
            box-shadow: 0 0 0 3px rgba(225, 29, 72, .12) !important;
        }

        .input-group:focus-within .input-icon-left {
            color: #e11d48 !important;
        }

        .input-group:hover .form-control {
            border-color: #cbd5e1 !important;
        }
    </style>

    <main id="main-container">
        <div class="hero-static">
            {{-- LEFT BRANDING --}}
            <div class="left-hero">
                <div class="left-inner">
                    <img src="{{ asset('media/keysoft_logo_text.png') }}" class="hero-logo" alt="Keysoft Logo">

                    <h1>
                        {{-- NLA: Next Level Accounting<br> --}}
                        <span style="font-size: 24px;">Warehouse Management System</span>
                    </h1>

                    <p>
                        One Step Beyond Accounting. Into Enterprise.
                    </p>

                    <div class="hero-divider"></div>

                    <div class="hero-points">
                        <div><i class="fa fa-shield-halved"></i> Secure & Role-Based Access</div>
                        <div><i class="fa fa-diagram-project"></i> Modular ERP Architecture</div>
                        <div><i class="fa fa-gauge-high"></i> High Performance System</div>
                    </div>

                    {{-- <div class="hero-footer">
                        <div>
                            <strong>99.9%</strong>
                            <span>System Uptime</span>
                        </div>
                        <div>
                            <strong>500+</strong>
                            <span>Active Companies</span>
                        </div>
                    </div> --}}
                </div>
            </div>

            {{-- RIGHT LOGIN --}}
            <div class="right-panel">
                {{-- <div class="login-card"> --}}
                <div style="width: 400px">
                    <div class="text-center mb-4">
                        <img src="{{ asset('media/keysoft_logo.png') }}" alt="Keysoft Logo" height="150px">
                        {{-- <p class="text-primary">Next Level Accounting</p> --}}
                    </div>
                    @foreach ($errors->all() as $error)
                        <div class="error-text">{{ $error }}</div>
                    @endforeach
                    <form method="POST" action="{{ route('login.submit') }}" autocomplete="off">
                        @csrf
                        <input type="hidden" name="code" value="{{ request('code', '') }}">
                        <input type="hidden" name="guid" value="{{ request('guid', '') }}">

                        <div class="mb-3">
                            <div class="input-group">
                                <input type="text" name="username" class="form-control form-control form-control-alt"
                                    placeholder="Username" required>
                                <i class="fas fa-user input-icon-left"></i>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="input-group">
                                <input type="password" name="password" class="form-control form-control form-control-alt"
                                    id="passwordInput" placeholder="Password" required>
                                <i class="fas fa-lock input-icon-left"></i>
                                <button type="button" class="password-toggle" id="passwordToggle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-login w-100">
                            <i class="fa fa-right-to-bracket me-1"></i> Sign In
                        </button>
                    </form>

                    <div class="login-footer">
                        Keysoft ERP © {{ date('Y') }} | Crafted by PT. Infotama Teknologi Indonesia.
                    </div>
                </div>
            </div>
        </div>
    </main>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.querySelector('input[name="username"]');
            if (usernameInput) {
                usernameInput.focus();
            }

            const loginBtn = document.querySelector('.btn-login');
            if (loginBtn) {
                loginBtn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });

                loginBtn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });

                const loginForm = document.querySelector('form');
                if (loginForm) {
                    loginForm.addEventListener('submit', function() {
                        loginBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Signing In...';
                        loginBtn.disabled = true;
                    });
                }
            }

            const errorMessages = document.querySelectorAll('.error-text');
            errorMessages.forEach((error, index) => {
                error.style.opacity = '0';
                error.style.transform = 'translateX(-20px)';

                setTimeout(() => {
                    error.style.transition = 'all 0.3s ease';
                    error.style.opacity = '1';
                    error.style.transform = 'translateX(0)';
                }, 100 * index);
            });

            const passwordToggle = document.getElementById('passwordToggle');
            const passwordInput = document.getElementById('passwordInput');

            if (passwordToggle && passwordInput) {
                passwordToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    const eyeIcon = this.querySelector('i');
                    if (type === 'text') {
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                    } else {
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    }

                    passwordInput.focus();
                });

                passwordToggle.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            }
        });
    </script>
@endsection
