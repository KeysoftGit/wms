@php
    $sidebarCompany = \App\Models\MsCompanyProfile::find(1);
@endphp
<header id="page-header">
    <!-- Header Content -->
    <div class="content-header">

        <!-- Left Section -->
        <div class="d-flex align-items-center">
            <!-- Toggle Sidebar -->
            <button type="button" class="btn btn-sm btn-alt-secondary me-2 d-lg-none" data-toggle="layout"
                data-action="sidebar_toggle">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <!-- Toggle Mini Sidebar -->
            <button type="button" class="btn btn-sm btn-alt-secondary me-2 d-none d-lg-inline-block" data-toggle="layout"
                data-action="sidebar_mini_toggle">
                <i class="fa fa-fw fa-ellipsis-v"></i>
            </button>

            <!-- Page Title - bisa ditambahkan sesuai kebutuhan -->
            {{-- <span class="d-none d-md-inline-block ms-2 fs-sm fw-semibold text-muted" id="page-title">
                @yield('title', 'Dashboard')
            </span> --}}
        </div>
        <!-- END Left Section -->

        <!-- Right Section -->
        <div class="d-flex align-items-center">

            <!-- Company & User Info -->
            <div class="d-none d-md-flex align-items-center me-3 px-3 border-end">
                <div class="text-end">
                    <div class="fw-semibold fs-sm text-primary">
                        {{ isset($sidebarCompany) ? $sidebarCompany->CompanyName : '' }}
                    </div>
                    <div class="fs-xs text-muted">
                        Hi, {{ auth()->user()->UserName }}
                    </div>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="dropdown d-inline-block ms-2">
                <button type="button" class="btn btn-sm btn-alt-secondary d-flex align-items-center p-2"
                    id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-2x fa-circle-notch text-primary"></i>
                        <div class="d-none d-sm-block text-start ms-2">
                            <span class="fw-semibold fs-sm">{{ auth()->user()->UserName }}</span>
                            <span class="badge bg-primary-light text-secondary ms-1" style="font-size: 10px;">WMS</span>
                        </div>
                    </div>
                    <i class="fa fa-fw fa-angle-down d-none d-sm-inline-block ms-2 mt-1"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-md dropdown-menu-end p-0 border-0 shadow-lg"
                    aria-labelledby="page-header-user-dropdown" style="border-radius: 12px; overflow: hidden;">

                    <!-- User Info Header -->
                    <div class="p-3 text-center bg-body-light border-bottom">
                        <i class="fa fa-3x fa-circle-notch text-primary mb-2"></i>
                        <p class="mb-0 fw-bold fs-6">{{ auth()->user()->UserName }}</p>
                        <p class="mb-0 fs-xs text-muted">{{ auth()->user()->email ?? '' }}</p>
                        <div class="mt-2">
                            <span class="badge bg-primary px-3 py-2 rounded-pill fs-xs fw-semibold">
                                <i class="fa fa-fw fa-building me-1"></i> {{ isset($sidebarCompany) ? $sidebarCompany->CompanyName : '' }}
                            </span>
                        </div>
                    </div>

                    <!-- Menu Items -->
                    <div class="p-2">
                        <a class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 rounded-3 mb-1"
                            href="{{ route('password') }}"
                            style="transition: all 0.2s ease;">
                            <span class="fs-sm fw-semibold">Change Password</span>
                            <i class="fa fa-fw fa-key opacity-50 ms-2"></i>
                        </a>

                        <div class="dropdown-divider my-1"></div>

                        <a class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 rounded-3 text-danger logout-hover"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            style="transition: all 0.2s ease; cursor: pointer;">
                            <span class="fs-sm fw-bold">Log Out</span>
                            <i class="fa fa-fw fa-sign-out-alt ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            <!-- END User Dropdown -->

        </div>
        <!-- END Right Section -->

    </div>
    <!-- END Header Content -->

    <!-- Hidden Logout Form -->
    <form autocomplete="off" id="logout-form" method="POST" action="{{ route('logout') }}" style="display: none;">
        {{ csrf_field() }}
    </form>

    <!-- Header Search (Hidden by default) -->
    <div id="page-header-search" class="overlay-header bg-body-extra-light" style="display: none;">
        <div class="content-header">
            <form autocomplete="off" class="w-100" action="be_pages_generic_search.html" method="POST">
                <div class="input-group">
                    <button type="button" class="btn btn-alt-danger" data-toggle="layout"
                        data-action="header_search_off">
                        <i class="fa fa-fw fa-times-circle"></i>
                    </button>
                    <input type="text" class="form-control" placeholder="Search or hit ESC.."
                        id="page-header-search-input" name="page-header-search-input">
                </div>
            </form>
        </div>
    </div>
    <!-- END Header Search -->

    <!-- Header Loader (Hidden by default) -->
    <div id="page-header-loader" class="overlay-header bg-body-extra-light" style="display: none;">
        <div class="content-header">
            <div class="w-100 text-center">
                <i class="fa fa-fw fa-circle-notch fa-spin"></i>
            </div>
        </div>
    </div>
    <!-- END Header Loader -->
</header>

<style>
    /* ===== STYLE HEADER ===== */

    /* Header styling */
    #page-header {
        background-color: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Content header padding */
    .content-header {
        padding: 0.75rem 1.5rem;
    }

    /* Button styling */
    .btn-alt-secondary {
        background-color: #f8f9fa;
        border: 1px solid rgba(0, 0, 0, 0.05);
        color: #495057;
        transition: all 0.2s ease;
    }

    .btn-alt-secondary:hover {
        background-color: #e9ecef;
        border-color: rgba(0, 0, 0, 0.1);
        color: #212529;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(50, 50, 93, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .btn-alt-secondary:active {
        transform: translateY(0);
    }

    /* User dropdown button */
    #page-header-user-dropdown {
        border-radius: 30px;
        padding: 0.375rem 0.75rem;
        background-color: #f8f9fa;
    }

    #page-header-user-dropdown:hover {
        background-color: #e9ecef;
    }

    /* Dropdown menu styling */
    .dropdown-menu {
        animation: dropdownFade 0.2s ease;
        border: none !important;
    }

    @keyframes dropdownFade {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Dropdown item styling */
    .dropdown-item {
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: #f0f4f8;
        transform: translateX(3px);
    }

    .dropdown-item i {
        transition: all 0.2s ease;
    }

    .dropdown-item:hover i {
        opacity: 1 !important;
        transform: translateX(2px);
    }

    /* Logout hover effect */
    .logout-hover:hover {
        background-color: #fff5f5 !important;
        color: #dc3545 !important;
    }

    .logout-hover:hover i {
        color: #dc3545 !important;
    }

    /* Badge styling */
    .bg-primary-light {
        background-color: rgba(225, 29, 72, 0.1);
    }

    .badge {
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    /* Text primary color */
    .text-primary {
        color: #e11d48 !important;
    }

    .bg-primary {
        background-color: #e11d48 !important;
    }

    /* Shadow */
    .shadow-lg {
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02) !important;
    }

    /* Page title */
    #page-title {
        position: relative;
        padding-left: 15px;
    }

    #page-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 18px;
        background-color: #e11d48;
        border-radius: 3px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .content-header {
            padding: 0.5rem 1rem;
        }

        #page-header-user-dropdown .fa-user-circle {
            font-size: 1.75rem !important;
        }
    }

    /* Loading animation */
    .fa-circle-notch.fa-spin {
        color: #e11d48;
    }

    /* Border end divider */
    .border-end {
        border-right: 1px solid rgba(0, 0, 0, 0.05) !important;
    }

    /* Dropdown divider */
    .dropdown-divider {
        margin: 0.5rem 0;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Version info */
    .bg-body-extra-light {
        background-color: #ffffff;
    }

    .fs-xs {
        font-size: 0.75rem;
    }

    /* Rounded corners */
    .rounded-3 {
        border-radius: 10px !important;
    }

    /* Smooth transitions */
    * {
        transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
        transition-duration: 0.2s;
        transition-timing-function: ease;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Set page title from meta or route
        const titleElement = document.getElementById('page-title');
        if (titleElement) {
            // You can customize this based on your route
            const path = window.location.pathname;
            let pageName = 'Dashboard';

            if (path.includes('dashboard')) pageName = 'Dashboard';
            else if (path.includes('company')) pageName = 'Company Profile';
            else if (path.includes('warehouse')) pageName = 'Warehouse';
            else if (path.includes('calendar')) pageName = 'Calendar';
            else if (path.includes('progress')) pageName = 'Progress Sheets';
            else if (path.includes('sync')) pageName = 'Sync Table';
            else if (path.includes('user')) pageName = 'User Management';
            else if (path.includes('control-panel')) pageName = 'Control Panel';
            else if (path.includes('activity-log')) pageName = 'Activity Log';
            else if (path.includes('accounting')) pageName = 'Accounting';
            else if (path.includes('inventory')) pageName = 'Inventory';
            else if (path.includes('purchase')) pageName = 'Purchase';
            else if (path.includes('sales')) pageName = 'Sales';
            else if (path.includes('stock')) pageName = 'Stock Management';
            else if (path.includes('production')) pageName = 'Production';
            else if (path.includes('fixed-asset')) pageName = 'Fixed Asset';
            else if (path.includes('finance')) pageName = 'Finance';
            else if (path.includes('journal')) pageName = 'Journal';

            titleElement.textContent = pageName;
        }

        // Close dropdown when clicking outside (default Bootstrap behavior, but add animation)
        const dropdowns = document.querySelectorAll('.dropdown-menu');
        dropdowns.forEach(dropdown => {
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // Adjust any responsive elements if needed
            }, 250);
        });

        // Add active state to current nav item in header if any
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-main-link');
        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    });

    // Toggle search overlay function (if needed)
    function toggleSearch() {
        const searchOverlay = document.getElementById('page-header-search');
        if (searchOverlay) {
            if (searchOverlay.style.display === 'none' || !searchOverlay.style.display) {
                searchOverlay.style.display = 'block';
                document.getElementById('page-header-search-input').focus();
            } else {
                searchOverlay.style.display = 'none';
            }
        }
    }

    // Toggle loader function (if needed)
    function toggleLoader(show = false) {
        const loader = document.getElementById('page-header-loader');
        if (loader) {
            loader.style.display = show ? 'block' : 'none';
        }
    }
</script>
