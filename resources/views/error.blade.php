<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Keysoft NLA - Access Error</title>

    <meta name="description" content="Keysoft ERP Online">
    <meta name="author" content="PT. Infotama Teknologi Indonesia">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph Meta -->
    <meta property="og:title" content="Keysoft ERP Online">
    <meta property="og:site_name" content="PT. Infotama Teknologi Indonesia">
    <meta property="og:description" content="Keysoft ERP Online - Access Error">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="">

    <!-- Icons -->
    <link rel="shortcut icon" href="{{ asset('media/favicons/favicon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('media/favicons/favicon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('media/favicons/apple-touch-icon-180x180.png') }}">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #0f1f3a 0%, #12234c 100%);
            color: #fff;
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(225, 29, 72, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(225, 29, 72, 0.1) 0%, transparent 50%);
            z-index: 0;
        }

        .error-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 40px;
            position: relative;
            z-index: 1;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeDown 0.8s ease-out;
        }

        .logo {
            width: 160px;
            height: auto;
        }

        .login-link {
            color: #fff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-link:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            padding: 60px 0;
        }

        .error-icon-container {
            margin-bottom: 40px;
            position: relative;
        }

        .error-icon {
            font-size: 120px;
            color: #e11d48;
            display: block;
            animation: pulse 2s infinite;
        }

        .error-icon-bg {
            position: absolute;
            font-size: 180px;
            color: rgba(225, 29, 72, 0.1);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: -1;
        }

        .error-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
            background: linear-gradient(135deg, #fff 0%, rgba(255, 255, 255, 0.8) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-subtitle {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 40px;
            max-width: 600px;
            line-height: 1.6;
        }

        .error-code {
            font-size: 140px;
            font-weight: 900;
            color: rgba(225, 29, 72, 0.15);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            user-select: none;
            pointer-events: none;
        }

        .error-details {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 40px;
            margin: 40px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 700px;
            width: 100%;
            text-align: left;
        }

        .error-details h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-details h3 i {
            color: #e11d48;
        }

        .error-list {
            list-style: none;
            padding: 0;
        }

        .error-list li {
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            font-size: 15px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .error-list li:last-child {
            border-bottom: none;
        }

        .error-list li i {
            color: #e11d48;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            margin-top: 50px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            padding: 16px 32px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 180px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(225, 29, 72, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .footer {
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 40px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }

        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            color: #e11d48;
        }

        /* Animations */
        @keyframes fadeDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.05);
                opacity: 0.9;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .error-page {
                padding: 30px 20px;
            }

            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .main-content {
                padding: 40px 0;
            }

            .error-title {
                font-size: 36px;
            }

            .error-icon {
                font-size: 80px;
            }

            .error-icon-bg {
                font-size: 120px;
            }

            .error-code {
                font-size: 100px;
            }

            .error-details {
                padding: 30px 20px;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
            }

            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .error-title {
                font-size: 28px;
            }

            .error-subtitle {
                font-size: 16px;
            }

            .error-icon {
                font-size: 60px;
            }

            .error-icon-bg {
                font-size: 90px;
            }

            .error-code {
                font-size: 80px;
            }
        }

        /* Floating elements */
        .floating-element {
            position: absolute;
            background: rgba(225, 29, 72, 0.1);
            border-radius: 50%;
            animation: float 20s infinite linear;
            z-index: 0;
        }

        .floating-1 {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-2 {
            width: 150px;
            height: 150px;
            bottom: 30%;
            right: 15%;
            animation-delay: -5s;
        }

        .floating-3 {
            width: 80px;
            height: 80px;
            top: 60%;
            left: 85%;
            animation-delay: -10s;
        }

        @keyframes float {
            0% {
                transform: translateY(0px) rotate(0deg);
            }

            25% {
                transform: translateY(-20px) rotate(90deg);
            }

            50% {
                transform: translateY(0px) rotate(180deg);
            }

            75% {
                transform: translateY(20px) rotate(270deg);
            }

            100% {
                transform: translateY(0px) rotate(360deg);
            }
        }

        /* Content animations */
        .main-content>* {
            opacity: 0;
            animation: fadeIn 0.8s ease-out forwards;
        }

        .main-content>*:nth-child(1) {
            animation-delay: 0.2s;
        }

        .main-content>*:nth-child(2) {
            animation-delay: 0.4s;
        }

        .main-content>*:nth-child(3) {
            animation-delay: 0.6s;
        }

        .main-content>*:nth-child(4) {
            animation-delay: 0.8s;
        }

        .main-content>*:nth-child(5) {
            animation-delay: 1s;
        }

        .main-content>*:nth-child(6) {
            animation-delay: 1.2s;
        }
    </style>
</head>

<body>
    <!-- Floating Background Elements -->
    <div class="floating-element floating-1"></div>
    <div class="floating-element floating-2"></div>
    <div class="floating-element floating-3"></div>

    <div class="error-page">
        <!-- Header -->
        <div class="header">
            <img src="{{ asset('media/keysoft_logo_text.png') }}" alt="Keysoft ERP" class="logo">
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Background Error Code -->
            <div class="error-code">ERROR</div>

            <!-- Error Icon -->
            <div class="error-icon-container">
                <i class="fas fa-exclamation-triangle error-icon-bg"></i>
                <i class="fas fa-exclamation-triangle error-icon"></i>
            </div>

            <!-- Error Message -->
            <h1 class="error-title">Invalid Customer Data</h1>
            <p class="error-subtitle">
                We cannot load your customer information. This may be due to an expired session,
                missing configuration, or database connectivity issues.
            </p>

            <!-- Error Details -->
            <div class="error-details">
                <h3><i class="fas fa-info-circle"></i> Possible Issues</h3>
                <ul class="error-list">
                    <li><i class="fas fa-clock"></i> Session has expired or is invalid</li>
                    <li><i class="fas fa-database"></i> Customer data not properly configured</li>
                    <li><i class="fas fa-building"></i> Company profile information missing</li>
                    <li><i class="fas fa-plug"></i> Database connection issue</li>
                    <li><i class="fas fa-user-shield"></i> Permission or access rights problem</li>
                    <li><i class="fas fa-server"></i> Server configuration error</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Go Back
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                Need technical assistance? <a href="mailto:support@keysoft.com">Contact our support team</a>
            </p>
            <p style="margin-top: 10px;">
                Keysoft ERP © {{ date('Y') }} • PT. Infotama Teknologi Indonesia
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('clicked')) {
                        this.style.transform = 'translateY(-3px)';
                    }
                });

                btn.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('clicked')) {
                        this.style.transform = 'translateY(0)';
                    }
                });

                // Click animation
                btn.addEventListener('click', function(e) {
                    if (!this.href || this.href.includes('javascript:')) return;

                    e.preventDefault();

                    // Add clicked class
                    this.classList.add('clicked');

                    // Store original content
                    const originalContent = this.innerHTML;

                    // Show loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    this.style.transform = 'scale(0.95)';

                    // Navigate after delay
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 500);
                });
            });

            // Pulsing effect for error icon
            const errorIcon = document.querySelector('.error-icon');
            setInterval(() => {
                errorIcon.style.transform = 'scale(1.05)';
                errorIcon.style.filter = 'drop-shadow(0 0 20px rgba(225, 29, 72, 0.5))';

                setTimeout(() => {
                    errorIcon.style.transform = 'scale(1)';
                    errorIcon.style.filter = 'drop-shadow(0 0 0 rgba(225, 29, 72, 0))';
                }, 300);
            }, 3000);

            // Auto-redirect after 60 seconds
            let countdown = 60;
            const countdownElement = document.createElement('div');
            countdownElement.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: rgba(0, 0, 0, 0.5);
                color: white;
                padding: 10px 15px;
                border-radius: 3px;
                font-size: 13px;
                z-index: 1000;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
            `;

            countdownElement.innerHTML = `Auto-redirect in <span id="countdown-timer">${countdown}</span> seconds`;
            document.body.appendChild(countdownElement);

            const countdownInterval = setInterval(() => {
                countdown--;
                document.getElementById('countdown-timer').textContent = countdown;

                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = "{{ route('login') }}";
                }
            }, 1000);

            // Remove countdown on click
            document.body.addEventListener('click', () => {
                clearInterval(countdownInterval);
                countdownElement.style.display = 'none';
            });
        });
    </script>
</body>

</html>
