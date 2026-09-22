@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Route;

    $routeActive = function (...$routeNames) {
        foreach ($routeNames as $routeName) {
            if (Route::is($routeName) || Route::is($routeName . '.*')) {
                return true;
            }
        }

        return false;
    };

    $sidebarCompany = \App\Models\MsCompanyProfile::find(1);
    $adds_on = DB::connection('keysoftone')->select(
        "SELECT
            aon.*,
            app.aplikasi_kode, app.aplikasi_nama, app.aplikasi_url, app.aplikasi_local_repo, app.isInternal, app.permission
        FROM custom_adds_on AS aon
        LEFT JOIN custom_apps AS app ON app.id = aon.custom_apps_id
        WHERE guid = ?",
        [session('guid')],
    );

    $enc_dec_key = 'neoIT2024';
    $datetime_now = new DateTime('now');
    $datetime_now->modify('+30 minutes');
    $expired_time = $datetime_now->format('Y-m-d H:i:s');
    $password_hashed = session('password_hash_web');

    $encrypted_password = urlencode(openssl_encrypt($password_hashed, 'AES-128-ECB', $enc_dec_key));
    $decrypted_password = openssl_decrypt(urldecode($encrypted_password), 'AES-128-ECB', $enc_dec_key);

    $encrypted_exptime = urlencode(openssl_encrypt($expired_time, 'AES-128-ECB', $enc_dec_key));
    $decrypted_exptime = openssl_decrypt(urldecode($encrypted_exptime), 'AES-128-ECB', $enc_dec_key);

    try {
        $dynamicReports = DB::table('Ms_DynamicReportHD')
            ->orderBy('ReportName')
            ->get()
            ->filter(function ($report) {
                $permission = 'dynamic_report_' . str_replace('-', '_', strtolower($report->Slug)) . '.view';

                return auth()
                    ->user()
                    ->hasAnyPermission(['admin', 'report.view', $permission]);
            });
    } catch (\Exception $e) {
        $dynamicReports = collect();
    }
@endphp



<nav id="sidebar" aria-label="Main Navigation">
    <!-- Side Header -->
    <div class="content-header"
        style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);">
        <!-- Logo -->
        <div>
            <a class="fw-semibold text-dual" href="#">
                <img src="{{ asset('media/keysoft_logo_text.png') }}" class="hero-logo" alt="Keysoft Logo" width="130px">
                <span class="bg-primary text-white pe-1 ps-1 mt-0">NLA</span>
            </a>
            <div style="font-size: 10px;">Warehouse Management System</div>
        </div>
        <!-- END Logo -->

        <!-- Extra -->
        <div>
            <a class="d-lg-none btn btn-sm btn-alt-secondary ms-1" data-toggle="layout" data-action="sidebar_close"
                href="javascript:void(0)">
                <i class="fa fa-fw fa-times"></i>
            </a>
        </div>
    </div>
    <!-- END Side Header -->

    <!-- Sidebar Scrolling -->
    <div class="js-sidebar-scroll">
        <!-- Side Navigation -->
        <div class="content-side">
            <!-- Sidebar Search -->
            <div class="mb-3" style="margin-left: -0.65rem; margin-right: -0.65rem;">
                <div class="input-group"
                    style="background: rgba(255, 255, 255, 0.08); border-radius: 6px; padding: 7px 12px; align-items: center; border: 1px solid rgba(255, 255, 255, 0.15);">
                    <i class="fa fa-fw fa-search text-white-50 me-2" style="font-size: 0.85rem;"></i>
                    <input type="text" id="sidebar-menu-search"
                        class="form-control border-0 bg-transparent text-white px-0 py-0" placeholder="Search menu..."
                        style="font-size: 0.875rem; box-shadow: none; outline: none;">
                    <button type="button" id="sidebar-menu-search-clear" class="btn btn-sm text-white-50 p-0 d-none"
                        style="border: none; background: none; font-size: 0.8rem;">
                        <i class="fa fa-fw fa-times"></i>
                    </button>
                </div>
            </div>

            <ul class="nav-main">
                <li class="nav-main-item">
                    <a class="nav-main-link {{ $routeActive('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">
                        <i class="nav-main-link-icon si si-speedometer"></i>
                        <span class="nav-main-link-name">Dashboard</span>
                    </a>
                </li>

                @php
                    $menu_access = ['admin', 'access.view'];
                    $activity_log_access = ['admin', 'activity_log.view'];
                @endphp

                {{-- @if (auth()->user()->hasAnyPermission($menu_access))
                    <li class="nav-main-item">
                        <a class="nav-main-link {{ $routeActive('access') ? 'active' : '' }}"
                            href="{{ route('access') }}">
                            <i class="nav-main-link-icon fa fa-cog"></i>
                            <span class="nav-main-link-name">Menu Access</span>
                        </a>
                    </li>
                @endif --}}

                @if (auth()->user()->hasAnyPermission(['admin', 'user_warehouse_mapping.view']))
                    <li class="nav-main-item">
                        <a class="nav-main-link {{ $routeActive('user_warehouse_mapping') ? 'active' : '' }}"
                            href="{{ route('user_warehouse_mapping.index') }}">
                            <i class="nav-main-link-icon fa fa-fw fa-warehouse"></i>
                            <span class="nav-main-link-name">User Warehouse Mapping</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasAnyPermission($activity_log_access))
                    <li class="nav-main-item">
                        <a class="nav-main-link {{ $routeActive('activity_log') ? 'active' : '' }}"
                            href="{{ route('activity_log') }}">
                            <i class="nav-main-link-icon fa fa-cog"></i>
                            <span class="nav-main-link-name">Activity Log</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasAnyPermission(['admin']))
                    <li class="nav-main-heading">Admin</li>
                    <li class="nav-main-item">
                        <a class="nav-main-link {{ $routeActive('sync') ? 'active' : '' }}" href="{{ route('sync') }}">
                            <i class="nav-main-link-icon fa fa-fw fa-cog"></i>
                            <span>Sync Table</span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a class="nav-main-link {{ $routeActive('control_panel') ? 'active' : '' }}"
                            href="{{ route('control_panel') }}">
                            <i class="nav-main-link-icon fa fa-fw fa-cog"></i>
                            <span class="nav-main-link-name">Control Panel</span>
                        </a>
                    </li>
                @endif



                {{-- MASTERS --}}
                @php
                    $permission_master = [
                        'admin',
                        'country.view',
                        'currency.view',
                        'district.view',
                        'sub_district.view',
                        'vehicle.view',
                        'unit.view',
                        'part_type.view',
                        'part_category.view',
                        'variant.view',
                        'specification.view',
                        'specification.view',
                        'part.view',
                        'qr_generator.view',
                        'division.view',
                        'employee.view',
                        'supplier.view',
                        'customer.view',
                        'coa.view',
                        'account_mapping.view',
                        'account_type_mapping.view',
                        'fa_category.view',
                        'fa_location.view',
                        'fixed_asset.view',
                        'warehouse.view',
                        'company_profile.edit',
                        'import.import',
                    ];
                    $isMasterActive = $routeActive('warehouse') || str_contains(url()->current(), '/inventory');
                @endphp
                @if (auth()->user()->hasAnyPermission($permission_master) && in_array('Master Data', session('portal_modul')))
                    {{-- <li class="nav-main-heading">Masters</li> --}}
                    <li class="nav-main-item {{ $isMasterActive ? 'active open' : '' }}">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="{{ $isMasterActive ? 'true' : 'false' }}" href="#">
                            <i class="nav-main-link-icon fa fa-fw fa-database"></i>
                            <span class="nav-main-link-name">Master</span>
                        </a>
                        <ul class="nav-main-submenu">
                            {{-- @if (auth()->user()->hasAnyPermission(['admin', 'company_profile.edit']))
                        <li class="nav-main-item">
                            <a class="nav-main-link {{ $routeActive('company') ? 'active' : '' }}"
                                href="{{ route('company') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-building"></i>
                                <span class="nav-main-link-name">Company Profile</span>
                            </a>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['admin', 'import.import']))
                        <li class="nav-main-item">
                            <a class="nav-main-link {{ $routeActive('import') ? 'active' : '' }}"
                                href="{{ route('import') }}">
                                <i class="nav-main-link-icon fas fa-fw fa-file-import"></i>
                                <span class="nav-main-link-name">Import Master</span>
                            </a>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['admin', 'country.view', 'currency.view', 'district.view', 'sub_district.view', 'vehicle.view']))
                        <li class="nav-main-item {{ str_contains(url()->current(), '/common') ? 'active open' : '' }}">
                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                aria-expanded="true" href="#">
                                <i class="nav-main-link-icon far fa-fw fa-rectangle-list"></i>
                                <span class="nav-main-link-name">Common</span>
                            </a>
                            <ul class="nav-main-submenu">
                                @if (auth()->user()->hasAnyPermission(['admin', 'country.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('common.country') ? 'active' : '' }}"
                                            href="{{ route('common.country') }}">
                                            <span class="nav-main-link-name">Country</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'currency.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('common.currency') ? 'active' : '' }}"
                                            href="{{ route('common.currency') }}">
                                            <span class="nav-main-link-name">Currency</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'district.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('common.district') ? 'active' : '' }}"
                                            href="{{ route('common.district') }}">
                                            <span class="nav-main-link-name">District</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'sub_district.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('common.subdistrict') ? 'active' : '' }}"
                                            href="{{ route('common.subdistrict') }}">
                                            <span class="nav-main-link-name">Sub District</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'vehicle.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('common.vehicle') ? 'active' : '' }}"
                                            href="{{ route('common.vehicle') }}">
                                            <span class="nav-main-link-name">Vehicle</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['admin', 'division.view', 'employee.view', 'supplier.view', 'customer.view']))
                        <li
                            class="nav-main-item {{ str_contains(url()->current(), '/user') && !str_contains(url()->current(), '/admin') ? 'active open' : '' }}">
                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                aria-expanded="true" href="#">
                                <i class="nav-main-link-icon far fa-fw fa-user"></i>
                                <span class="nav-main-link-name">User</span>
                            </a>
                            <ul class="nav-main-submenu">
                                @if (auth()->user()->hasAnyPermission(['admin', 'division.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('user.division') ? 'active' : '' }}"
                                            href="{{ route('user.division') }}">
                                            <span class="nav-main-link-name">Division</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'employee.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('user.employee') ? 'active' : '' }}"
                                            href="{{ route('user.employee') }}">
                                            <span class="nav-main-link-name">Employee</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'supplier.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('user.supplier') ? 'active' : '' }}"
                                            href="{{ route('user.supplier') }}">
                                            <span class="nav-main-link-name">Supplier</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'customer.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('user.customer') ? 'active' : '' }}"
                                            href="{{ route('user.customer') }}">
                                            <span class="nav-main-link-name">Customer</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['admin', 'coa.view', 'account_mapping.view', 'account_type_mapping.view', 'ua.view']))
                        <li
                            class="nav-main-item {{ str_contains(url()->current(), '/accounting') ? 'active open' : '' }}">
                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                aria-expanded="true" href="#">
                                <i class="nav-main-link-icon far fa-fw fa-money-bill-1"></i>
                                <span class="nav-main-link-name">Accounting</span>
                            </a>
                            <ul class="nav-main-submenu">
                                @if (auth()->user()->hasAnyPermission(['admin', 'coa.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('accounting.coa') ? 'active' : '' }}"
                                            href="{{ route('accounting.coa') }}">
                                            <span class="nav-main-link-name">Chart of Account</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'account_mapping.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('accounting.mapping') ? 'active' : '' }}"
                                            href="{{ route('accounting.mapping') }}">
                                            <span class="nav-main-link-name">Account Mapping</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'account_type_mapping.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('accounting.mapping_type') ? 'active' : '' }}"
                                            href="{{ route('accounting.mapping_type') }}">
                                            <span class="nav-main-link-name">Account Type Mapping</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'ua.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('accounting.ua') ? 'active' : '' }}"
                                            href="{{ route('accounting.ua') }}">
                                            <span class="nav-main-link-name">User Approval</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['admin', 'fa_category.view', 'fa_location.view', 'fixed_asset.view']))
                        <li
                            class="nav-main-item {{ str_contains(url()->current(), '/fixed-asset') ? 'active open' : '' }}">
                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                aria-expanded="true" href="#">
                                <i class="nav-main-link-icon si si-grid"></i>
                                <span class="nav-main-link-name">Fixed Asset</span>
                            </a>
                            <ul class="nav-main-submenu">
                                @if (auth()->user()->hasAnyPermission(['admin', 'fa_category.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('fixed_asset.category') ? 'active' : '' }}"
                                            href="{{ route('fixed_asset.category') }}">
                                            <span class="nav-main-link-name">Category</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'fa_location.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('fixed_asset.location') ? 'active' : '' }}"
                                            href="{{ route('fixed_asset.location') }}">
                                            <span class="nav-main-link-name">Location</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'fixed_asset.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('fixed_asset') && !$routeActive('fixed_asset.category', 'fixed_asset.location') ? 'active' : '' }}"
                                            href="{{ route('fixed_asset') }}">
                                            <span class="nav-main-link-name">Fixed Asset</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif --}}
                            @if (auth()->user()->hasAnyPermission(['admin', 'warehouse.view']))
                                <li class="nav-main-item">
                                    <a class="nav-main-link {{ $routeActive('warehouse') ? 'active' : '' }}"
                                        href="{{ route('warehouse') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-warehouse"></i>
                                        <span class="nav-main-link-name">Warehouse</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->hasAnyPermission([
                                        'admin',
                                        'unit.view',
                                        'part_type.view',
                                        'part_category.view',
                                        'variant.view',
                                        'specification.view',
                                        'specification.view',
                                        'part.view',
                                        'qr_generator.view',
                                    ]))
                                <li
                                    class="nav-main-item {{ str_contains(url()->current(), '/inventory') ? 'active open' : '' }}">
                                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                        aria-haspopup="true" aria-expanded="true" href="#">
                                        <i class="nav-main-link-icon si si-drawer"></i>
                                        <span class="nav-main-link-name">Inventory</span>
                                    </a>
                                    <ul class="nav-main-submenu">
                                        {{-- @if (auth()->user()->hasAnyPermission(['admin', 'unit.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('inventory.unit') ? 'active' : '' }}"
                                            href="{{ route('inventory.unit') }}">
                                            <span class="nav-main-link-name">Unit</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'part_type.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('inventory.type') ? 'active' : '' }}"
                                            href="{{ route('inventory.type') }}">
                                            <span class="nav-main-link-name">Type</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'part_category.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('inventory.category') ? 'active' : '' }}"
                                            href="{{ route('inventory.category') }}">
                                            <span class="nav-main-link-name">Category</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'variant.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('inventory.variant') ? 'active' : '' }}"
                                            href="{{ route('inventory.variant') }}">
                                            <span class="nav-main-link-name">Variant</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->hasAnyPermission(['admin', 'specification.view']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link {{ $routeActive('inventory.specification') ? 'active' : '' }}"
                                            href="{{ route('inventory.specification') }}">
                                            <span class="nav-main-link-name">Specification</span>
                                        </a>
                                    </li>
                                @endif --}}
                                        @if (auth()->user()->hasAnyPermission(['admin', 'part.view']))
                                            <li class="nav-main-item">
                                                <a class="nav-main-link {{ $routeActive('inventory.part') ? 'active' : '' }}"
                                                    href="{{ route('inventory.part') }}">
                                                    <span class="nav-main-link-name">Part</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if (auth()->user()->hasAnyPermission(['admin', 'qr_generator.view']))
                                            <li class="nav-main-item">
                                                <a class="nav-main-link {{ $routeActive('inventory.qr_generator') ? 'active' : '' }}"
                                                    href="{{ route('inventory.qr_generator') }}">
                                                    <span class="nav-main-link-name">QR Generator</span>
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif



                {{-- PURCHASE --}}
                @php
                    $permission_purchase = [
                        'admin',
                        'prs.view',
                        'psa.view',
                        'po.view',
                        'gr.view',
                        'pi.view',
                        'dp.view',
                        'vp.view',
                        'pr.view',
                        'pr_execute.view',
                    ];
                    $isPurchaseActive = $routeActive('gr', 'pr_execute');
                @endphp
                @if (auth()->user()->hasAnyPermission($permission_purchase) && in_array('Purchase', session('portal_modul')))
                    {{-- <li class="nav-main-heading">Purchase</li> --}}
                    <li class="nav-main-item {{ $isPurchaseActive ? 'active open' : '' }}">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="{{ $isPurchaseActive ? 'true' : 'false' }}" href="#">
                            <i class="nav-main-link-icon fa fa-fw fa-shopping-bag"></i>
                            <span class="nav-main-link-name">Purchase</span>
                        </a>
                        <ul class="nav-main-submenu">
                            {{-- @if (auth()->user()->hasAnyPermission(['admin', 'prs.view']))
                            <a class="nav-main-link {{ $routeActive('prs') ? 'active' : '' }}"
                                href="{{ route('prs') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-bag-shopping"></i>
                                <span class="nav-main-link-name">Purchase Request Settle</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'psa.view']))
                            <a class="nav-main-link {{ $routeActive('psa') ? 'active' : '' }}"
                                href="{{ route('psa') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-bag-shopping"></i>
                                <span class="nav-main-link-name">Purchase Settle Approval</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'po.view']))
                            <a class="nav-main-link {{ $routeActive('po') ? 'active' : '' }}"
                                href="{{ route('po') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-bag-shopping"></i>
                                <span class="nav-main-link-name">Purchase Order</span>
                            </a>
                        @endif --}}
                            @if (auth()->user()->hasAnyPermission(['admin', 'gr.view']))
                                <li class="nav-main-item" data-permission="gr.view gr goods_receiving">
                                    <a class="nav-main-link {{ $routeActive('gr') ? 'active' : '' }}"
                                        href="{{ route('gr') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-truck-ramp-box"></i>
                                        <span class="nav-main-link-name">Goods Receiving</span>
                                    </a>
                                </li>
                            @endif
                            {{-- @if (auth()->user()->hasAnyPermission(['admin', 'pi.view']))
                            <a class="nav-main-link {{ $routeActive('pi') ? 'active' : '' }}"
                                href="{{ route('pi') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-file-invoice"></i>
                                <span class="nav-main-link-name">Purchase Invoice</span>
                            </a>
                        @endif --}}
                            {{-- @if (auth()->user()->hasAnyPermission(['admin', 'dp.view']))
                            <a class="nav-main-link {{ $routeActive('dp') ? 'active' : '' }}"
                                href="{{ route('dp') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-cart-arrow-down"></i>
                                <span class="nav-main-link-name">Direct Purchase</span>
                            </a>
                        @endif --}}
                            @if (auth()->user()->hasAnyPermission(['admin', 'pr_execute.view']))
                                <li class="nav-main-item"
                                    data-permission="pr_execute.view pr_execute purchase_return_execute">
                                    <a class="nav-main-link {{ $routeActive('pr_execute') ? 'active' : '' }}"
                                        href="{{ route('pr_execute') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-arrow-rotate-left"></i>
                                        <span class="nav-main-link-name">Purchase Return Execute</span>
                                    </a>
                                </li>
                            @endif
                            {{-- @if (auth()->user()->hasAnyPermission(['admin', 'vp.view']))
                            <a class="nav-main-link {{ $routeActive('vp') ? 'active' : '' }}"
                                href="{{ route('vp') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-money-bill"></i>
                                <span class="nav-main-link-name">Vendor Payment</span>
                            </a>
                        @endif --}}
                            {{-- @if (auth()->user()->hasAnyPermission(['admin', 'pr.view']))
                            <a class="nav-main-link {{ $routeActive('pr') ? 'active' : '' }}"
                                href="{{ route('pr') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-arrow-rotate-left"></i>
                                <span class="nav-main-link-name">Purchase Return</span>
                            </a>
                        @endif --}}
                        </ul>
                    </li>
                @endif



                {{-- SALES --}}
                @php
                    $permission_sales = ['admin', 'do.view', 'do_execute.view'];
                    $isSalesActive = $routeActive('do', 'do_execute');
                @endphp
                @if (auth()->user()->hasAnyPermission($permission_sales) && in_array('Sales', session('portal_modul')))
                    <li class="nav-main-item {{ $isSalesActive ? 'active open' : '' }}">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="{{ $isSalesActive ? 'true' : 'false' }}" href="#">
                            <i class="nav-main-link-icon fa fa-fw fa-truck"></i>
                            <span class="nav-main-link-name">Stock Out</span>
                        </a>
                        <ul class="nav-main-submenu">
                            {{-- @if (auth()->user()->hasAnyPermission(['admin', 'do.view']))
                                <li class="nav-main-item" data-permission="do.view do delivery_order">
                                    <a class="nav-main-link {{ $routeActive('do') ? 'active' : '' }}"
                                        href="{{ route('do') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-truck"></i>
                                        <span class="nav-main-link-name">Delivery Order</span>
                                    </a>
                                </li>
                            @endif --}}
                            @if (auth()->user()->hasAnyPermission(['admin', 'do_execute.view']))
                                <li class="nav-main-item"
                                    data-permission="do_execute.view do_execute delivery_order_execute">
                                    <a class="nav-main-link {{ $routeActive('do_execute') ? 'active' : '' }}"
                                        href="{{ route('do_execute') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-check"></i>
                                        <span class="nav-main-link-name">Delivery Order Execute</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif



                {{-- STOCK --}}
                @php
                    $permission_stock = [
                        'admin',
                        'transfer.view',
                        'transfer_request.view',
                        'opname.view',
                        'stock_adj.view',
                        'stock_monitor.view',
                        'part_usage.view',
                        'stock_report.view',
                    ];
                    $isStockActive = $routeActive(
                        'usage',
                        'transfer',
                        'transfer_request',
                        'transfer_execute',
                        'transfer_receive',
                        'opname',
                        'adjust',
                        'monitor',
                        'stock_report',
                    );
                @endphp
                @if (auth()->user()->hasAnyPermission($permission_stock) && in_array('Stock', session('portal_modul')))
                    {{-- <li class="nav-main-heading">Stock</li> --}}
                    <li class="nav-main-item {{ $isStockActive ? 'active open' : '' }}">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="{{ $isStockActive ? 'true' : 'false' }}" href="#">
                            <i class="nav-main-link-icon fa fa-fw fa-boxes-stacked"></i>
                            <span class="nav-main-link-name">Stock</span>
                        </a>
                        <ul class="nav-main-submenu">
                            @if (auth()->user()->hasAnyPermission(['admin', 'part_usage.view']))
                                <li class="nav-main-item" data-permission="part_usage.view usage part_usage">
                                    <a class="nav-main-link {{ $routeActive('usage') ? 'active' : '' }}"
                                        href="{{ route('usage') }}">
                                        <i class="nav-main-link-icon fa fa-wrench fa-right-left"></i>
                                        <span class="nav-main-link-name">Part Usage</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->hasAnyPermission(['admin', 'transfer.view']))
                                <li class="nav-main-item" data-permission="transfer.view transfer item_transfer">
                                    <a class="nav-main-link {{ $routeActive('transfer') ? 'active' : '' }}"
                                        href="{{ route('transfer') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-right-left"></i>
                                        <span class="nav-main-link-name">Item Transfer</span>
                                    </a>
                                </li>
                            @endif
                            {{-- @if (auth()->user()->hasAnyPermission(['admin', 'transfer_request.view']))
                            <li class="nav-main-item" data-permission="transfer_request.view transfer_request">
                                <a class="nav-main-link {{ $routeActive('transfer_request') ? 'active' : '' }}"
                                    href="{{ route('transfer_request') }}">
                                    <i class="nav-main-link-icon fa fa-fw fa-right-left"></i>
                                    <span class="nav-main-link-name">Item Transfer Request</span>
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'transfer_execute.view']))
                            <li class="nav-main-item" data-permission="transfer_execute.view transfer_execute">
                                <a class="nav-main-link {{ $routeActive('transfer_execute') ? 'active' : '' }}"
                                    href="{{ route('transfer_execute') }}">
                                    <i class="nav-main-link-icon fa fa-fw fa-right-left"></i>
                                    <span class="nav-main-link-name">Item Transfer Execute</span>
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'transfer_receive.view']))
                            <li class="nav-main-item" data-permission="transfer_receive.view transfer_receive">
                                <a class="nav-main-link {{ $routeActive('transfer_receive') ? 'active' : '' }}"
                                    href="{{ route('transfer_receive') }}">
                                    <i class="nav-main-link-icon fa fa-fw fa-right-left"></i>
                                    <span class="nav-main-link-name">Item Transfer Receive</span>
                                </a>
                            </li>
                        @endif --}}
                            @if (auth()->user()->hasAnyPermission(['admin', 'opname.view']))
                                <li class="nav-main-item" data-permission="opname.view opname stock_opname">
                                    <a class="nav-main-link {{ $routeActive('opname') ? 'active' : '' }}"
                                        href="{{ route('opname') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-list-check"></i>
                                        <span class="nav-main-link-name">Stock Opname</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->hasAnyPermission(['admin', 'stock_adj.view']))
                                <li class="nav-main-item" data-permission="stock_adj.view adjust stock_adjustment">
                                    <a class="nav-main-link {{ $routeActive('adjust') ? 'active' : '' }}"
                                        href="{{ route('adjust') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-sliders"></i>
                                        <span class="nav-main-link-name">Stock Adjustment</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->hasAnyPermission(['admin', 'stock_monitor.view']))
                                <li class="nav-main-item"
                                    data-permission="stock_monitor.view monitor stock_monitoring">
                                    <a class="nav-main-link {{ $routeActive('monitor') ? 'active' : '' }}"
                                        href="{{ route('monitor') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-desktop"></i>
                                        <span class="nav-main-link-name">Stock Monitoring</span>
                                    </a>
                                </li>
                            @endif
                            {{-- @if (auth()->user()->hasAnyPermission(['admin', 'stock_report.view']))
                                <li class="nav-main-item" data-permission="stock_report.view stock_report">
                                    <a class="nav-main-link {{ $routeActive('stock_report') ? 'active' : '' }}"
                                        href="{{ route('stock_report') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-cubes"></i>
                                        <span class="nav-main-link-name">Stock Report</span>
                                    </a>
                                </li>
                            @endif --}}
                        </ul>
                    </li>
                @endif



                {{-- FIXED ASSET --}}
                @php
                    $permission_fixedasset = ['admin', 'fa_revaluation.view', 'fa_movement.view', 'fa_disposal.view'];
                @endphp
                {{-- @if (auth()->user()->hasAnyPermission($permission_fixedasset) && in_array('Fixed Asset', session('portal_modul')))
                    <li class="nav-main-heading">Fixed Asset</li>
                    <li class="nav-main-item">
                        @if (auth()->user()->hasAnyPermission(['admin', 'fa_revaluation.view']))
                            <a class="nav-main-link {{ $routeActive('fa_reval') ? 'active' : '' }}"
                                href="{{ route('fa_reval') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-repeat"></i>
                                <span class="nav-main-link-name">Fixed Asset Revaluation</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'fa_movement.view']))
                            <a class="nav-main-link {{ $routeActive('fa_movement') ? 'active' : '' }}"
                                href="{{ route('fa_movement') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-right-left"></i>
                                <span class="nav-main-link-name">Fixed Asset Movement</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'fa_disposal.view']))
                            <a class="nav-main-link {{ $routeActive('fa_disposal') ? 'active' : '' }}"
                                href="{{ route('fa_disposal') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-trash-can"></i>
                                <span class="nav-main-link-name">Fixed Asset Disposal</span>
                            </a>
                        @endif
                    </li>
                @endif --}}



                {{-- FINANCE --}}
                @php
                    $permission_finance = [
                        'admin',
                        'other_revenue.view',
                        'other_payment.view',
                        'bank_recon.view',
                        'bh.view',
                        'bp.view',
                    ];
                @endphp
                {{-- @if (auth()->user()->hasAnyPermission($permission_finance) && in_array('Finance', session('portal_modul')))
                    <li class="nav-main-heading">Finance</li>
                    <li class="nav-main-item">
                        @if (auth()->user()->hasAnyPermission(['admin', 'other_revenue.view']))
                            <a class="nav-main-link {{ $routeActive('revenue') ? 'active' : '' }}"
                                href="{{ route('revenue') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-wallet"></i>
                                <span class="nav-main-link-name">Other Revenue</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'other_payment.view']))
                            <a class="nav-main-link {{ $routeActive('payment') ? 'active' : '' }}"
                                href="{{ route('payment') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-money-check"></i>
                                <span class="nav-main-link-name">Other Payment</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'bank_recon.view']))
                            <a class="nav-main-link {{ $routeActive('br') ? 'active' : '' }}"
                                href="{{ route('br') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-clipboard-check"></i>
                                <span class="nav-main-link-name">Bank Reconciliation</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'bh.view']))
                            <a class="nav-main-link {{ $routeActive('bh') ? 'active' : '' }}"
                                href="{{ route('bh') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-book"></i>
                                <span class="nav-main-link-name">Debt</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'bp.view']))
                            <a class="nav-main-link {{ $routeActive('bp') ? 'active' : '' }}"
                                href="{{ route('bp') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-book"></i>
                                <span class="nav-main-link-name">Receivable</span>
                            </a>
                        @endif
                    </li>
                @endif --}}



                {{-- JOURNAL --}}
                @php
                    $permission_journal = ['admin', 'journal_manual.view', 'journal.view', 'end_of_period.edit'];
                @endphp
                {{-- @if (auth()->user()->hasAnyPermission($permission_journal) && in_array('Journal', session('portal_modul')))
                    <li class="nav-main-heading">Journal</li>
                    <li class="nav-main-item">
                        @if (auth()->user()->hasAnyPermission(['admin', 'journal_manual.view']))
                            <a class="nav-main-link {{ $routeActive('journal_manual') ? 'active' : '' }}"
                                href="{{ route('journal_manual') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-pen-to-square"></i>
                                <span class="nav-main-link-name">Journal Manual</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'journal.view']))
                            <a class="nav-main-link {{ $routeActive('accounting.journal') ? 'active' : '' }}"
                                href="{{ route('accounting.journal') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-book"></i>
                                <span class="nav-main-link-name">Journal List</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasAnyPermission(['admin', 'end_of_period.edit']))
                            <a class="nav-main-link {{ $routeActive('accounting.end_of_period') ? 'active' : '' }}"
                                href="{{ route('accounting.end_of_period') }}">
                                <i class="nav-main-link-icon fa fa-fw fa-check-to-slot"></i>
                                <span class="nav-main-link-name">End of Period Posting</span>
                            </a>
                        @endif
                    </li>
                @endif --}}



                {{-- REPORT --}}
                @php
                    $permission_report = ['admin', 'report.view'];
                    $isReportViewActive = $routeActive('report') && !$routeActive('report.dynamic');
                    $isReportActive = $isReportViewActive || $routeActive('report.dynamic');
                @endphp
                @if (auth()->user()->hasAnyPermission($permission_report) || $dynamicReports->isNotEmpty())
                    {{-- <li class="nav-main-heading">Report</li> --}}
                    <li class="nav-main-item {{ $isReportActive ? 'active open' : '' }}">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="{{ $isReportActive ? 'true' : 'false' }}" href="#">
                            <i class="nav-main-link-icon fa fa-fw fa-book"></i>
                            <span class="nav-main-link-name">Report</span>
                        </a>
                        <ul class="nav-main-submenu">
                            @if (auth()->user()->hasAnyPermission($permission_report))
                                <li class="nav-main-item" data-permission="report.view report">
                                    <a class="nav-main-link {{ $isReportViewActive ? 'active' : '' }}"
                                        href="{{ route('report') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-book"></i>
                                        <span class="nav-main-link-name">View Report</span>
                                    </a>
                                </li>
                            @endif
                            @if ($dynamicReports->isNotEmpty())
                                <li class="nav-main-item" data-permission="report.dynamic dynamic_report">
                                    <a class="nav-main-link {{ $routeActive('report.dynamic') ? 'active' : '' }}"
                                        href="{{ route('report.dynamic.list') }}">
                                        <i class="nav-main-link-icon fa fa-fw fa-table"></i>
                                        <span class="nav-main-link-name">View Dynamic Report</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

            </ul>
        </div>
        <!-- END Side Navigation -->
    </div>
    <!-- END Sidebar Scrolling -->
</nav>

<style>
    /* ==== TAMBAHAN STYLE DARI SIDEBAR KEDUA ==== */
    #sidebar {
        display: flex;
        flex-direction: column;
        height: 100vh;
        /* Full height */
        overflow: hidden;
        /* Prevent main sidebar overflow */
    }

    /* Sticky header - gunakan class yang sudah ada */
    #sidebar .content-header {
        position: sticky;
        top: 0;
        z-index: 10;
        flex-shrink: 0;
        /* Prevent header from shrinking */
        /* background: linear-gradient(135deg, rgba(30, 30, 47, 0.95) 0%, rgba(45, 45, 58, 0.95) 100%); */
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    /* Scrollable body - gunakan class yang sudah ada */
    #sidebar .js-sidebar-scroll {
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: hidden;
        height: calc(100vh - 70px);
        /* Kurangi tinggi header (sesuaikan dengan tinggi header Anda) */
        min-height: 0;
        /* Penting untuk flex child scrolling */
    }

    /* Perbaiki scrollbar styling */
    #sidebar .js-sidebar-scroll::-webkit-scrollbar {
        width: 8px;
    }

    #sidebar .js-sidebar-scroll::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        margin: 4px 0;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }

    #sidebar .js-sidebar-scroll::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.4);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    #sidebar .js-sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(225, 29, 72, 0.6);
        border: 1px solid rgba(225, 29, 72, 0.2);
        box-shadow: 0 0 10px rgba(225, 29, 72, 0.3);
    }

    /* Pastikan konten pertama tidak terpotong */
    #sidebar .js-sidebar-scroll .nav-main {
        padding-top: 5px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        #sidebar .js-sidebar-scroll {
            height: calc(100vh - 60px);
            /* Sesuaikan untuk mobile */
        }
    }

    /* Scrollbar style yang lebih modern */
    .js-sidebar-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .js-sidebar-scroll::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        margin: 4px 0;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }

    .js-sidebar-scroll::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.4);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .js-sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(225, 29, 72, 0.6);
        border: 1px solid rgba(225, 29, 72, 0.2);
        box-shadow: 0 0 10px rgba(225, 29, 72, 0.3);
    }

    .js-sidebar-scroll::-webkit-scrollbar-thumb:active {
        background: rgba(225, 29, 72, 0.8);
    }

    .js-sidebar-scroll {
        scrollbar-width: thin;
        scrollbar-color: rgba(148, 163, 184, 0.4) rgba(255, 255, 255, 0.03);
    }

    /* Animasi chevron saat submenu dibuka */
    .nav-main-link[aria-expanded="true"] .fa-chevron-down {
        transform: rotate(180deg);
        transition: transform 0.25s ease;
    }

    .fa-chevron-down {
        transition: transform 0.25s ease;
    }

    /* Hover effect yang lebih smooth */
    .nav-main-link:hover::before {
        content: '';
        position: absolute;
        left: 0;
        top: 6px;
        bottom: 6px;
        width: 4px;
        background: rgba(225, 29, 72, 0.5);
        border-radius: 0 4px 4px 0;
    }

    .nav-main-has-submenu ul .nav-main-link:hover::before {
        left: 12px;
        top: 8px;
        bottom: 8px;
        width: 3px;
        background: rgba(225, 29, 72, 0.5);
    }

    /* Active state yang lebih tegas */
    .nav-main-link.active:hover::before {
        background: #e11d48;
    }

    /* Bulatan dan garis style dari sidebar kedua (sudah ada, kita perbaiki sedikit) */
    .nav-main-has-submenu ul {
        border-radius: 10px;
    }

    .nav-main-has-submenu ul .nav-main-link {
        border-radius: 6px;
    }

    /* Smooth transition untuk semua link */
    .nav-main-link {
        transition: all 0.25s ease;
    }

    .nav-main-link .nav-main-link-icon {
        transition: color 0.25s ease;
    }

    /* Submenu animation */
    .collapse {
        transition: all 0.2s ease;
    }

    /* ========== PERBAIKAN PADDING CHILD MENU ========== */

    /* Perbaikan padding utama untuk child menu */
    .nav-main-has-submenu ul .nav-main-link {
        padding: 10px 16px 10px 40px !important;
        margin: 2px 8px !important;
        font-size: 13.5px;
        color: #b6c0d1 !important;
        border-radius: 8px;
        line-height: 1.5;
    }

    /* Padding untuk nested submenu (level 3) */
    .nav-main-has-submenu ul .nav-main-has-submenu ul .nav-main-link {
        padding-left: 55px !important;
        padding-top: 8px !important;
        padding-bottom: 8px !important;
        margin: 1px 6px !important;
        font-size: 13px !important;
    }

    /* Padding untuk nested submenu level 4 (jika ada) */
    .nav-main-has-submenu ul .nav-main-has-submenu ul .nav-main-has-submenu ul .nav-main-link {
        padding-left: 70px !important;
    }

    /* Jarak antar item dalam submenu */
    .nav-main-has-submenu ul li {
        margin-bottom: 1px;
    }

    /* Perbaikan posisi garis vertikal */
    .nav-main-has-submenu ul::before {
        left: 31px;
        top: 10px;
        bottom: 10px;
    }

    /* Perbaikan posisi garis horizontal */
    .nav-main-has-submenu ul>.nav-main-item::before {
        left: 28px;
        width: 12px;
    }

    /* Perbaikan posisi bulatan */
    .nav-main-has-submenu ul>.nav-main-item::after {
        left: 37px;
        width: 6px;
        height: 6px;
    }

    /* Perbaikan posisi garis untuk nested submenu */
    .nav-main-has-submenu ul .nav-main-has-submenu ul::before {
        left: 44px;
    }

    .nav-main-has-submenu ul .nav-main-has-submenu ul>.nav-main-item::before {
        left: 41px;
        width: 10px;
    }

    .nav-main-has-submenu ul .nav-main-has-submenu ul>.nav-main-item::after {
        left: 51px;
        width: 5px;
        height: 5px;
    }

    /* ========== STYLE KHUSUS ACTIVE STATE ========== */

    /* Style untuk parent menu yang memiliki child active */
    .nav-main-has-submenu>.nav-main-link[aria-expanded="true"] {
        background: rgba(225, 29, 72, 0.08);
        color: #ffffff !important;
    }

    .nav-main-has-submenu>.nav-main-link[aria-expanded="true"] .nav-main-link-icon {
        color: #e11d48;
    }

    .nav-main-has-submenu>.nav-main-link[aria-expanded="true"]::before {
        content: '';
        position: absolute;
        left: 0;
        top: 6px;
        bottom: 6px;
        width: 4px;
        background: #e11d48;
        opacity: 0.7;
    }

    /* Style untuk active link di submenu */
    .nav-main-has-submenu ul .nav-main-link.active {
        background: linear-gradient(90deg, rgba(225, 29, 72, 0.15) 0%, rgba(225, 29, 72, 0.05) 100%);
        color: #ffffff !important;
        font-weight: 600;
        border-left: 3px solid #e11d48;
        box-shadow: inset 0 0 0 1px rgba(225, 29, 72, 0.2);
    }

    .nav-main-has-submenu ul .nav-main-link.active::before {
        display: none;
    }

    .nav-main-has-submenu ul .nav-main-link.active span {
        color: #ffffff;
        font-weight: 600;
    }

    /* Style untuk bulatan active */
    .nav-main-has-submenu ul>.nav-main-item.active::after {
        background: #e11d48 !important;
        opacity: 1;
        box-shadow: 0 0 0 2px rgba(225, 29, 72, 0.3);
        transform: translateY(-50%) scale(1.2);
    }

    /* Style untuk item yang memiliki submenu dan sedang active/open */
    .nav-main-has-submenu ul>.nav-main-has-submenu>.nav-main-link[aria-expanded="true"] {
        background: rgba(225, 29, 72, 0.1);
        color: #ffffff !important;
        font-weight: 600;
        border-left: 3px solid #e11d48;
    }

    .nav-main-has-submenu ul>.nav-main-has-submenu>.nav-main-link[aria-expanded="true"] .nav-main-link-icon {
        color: #e11d48;
    }

    /* Style untuk link yang sedang di-hover dan active */
    .nav-main-has-submenu ul .nav-main-link.active:hover {
        background: linear-gradient(90deg, rgba(225, 29, 72, 0.25) 0%, rgba(225, 29, 72, 0.1) 100%);
        box-shadow: inset 0 0 0 1px rgba(225, 29, 72, 0.4);
    }

    /* Style untuk icon saat active */
    .nav-main-has-submenu ul .nav-main-link.active .nav-main-link-icon {
        color: #e11d48 !important;
        filter: drop-shadow(0 0 2px rgba(225, 29, 72, 0.5));
    }

    /* Tambahan efek glow untuk active state */
    /* @keyframes activeGlow {
        0% { box-shadow: inset 0 0 0 1px rgba(225, 29, 72, 0.2); }
        50% { box-shadow: inset 0 0 0 2px rgba(225, 29, 72, 0.3); }
        100% { box-shadow: inset 0 0 0 1px rgba(225, 29, 72, 0.2); }
    } */

    /* .nav-main-link.active {
        animation: activeGlow 3s infinite;
    } */

    /* Style untuk text pada link active */
    .nav-main-link.active .nav-main-link-name {
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    /* Style untuk parent dari active link */
    .nav-main-has-submenu:has(.nav-main-link.active)>.nav-main-link {
        background: rgba(225, 29, 72, 0.08);
        border-left: 3px solid rgba(225, 29, 72, 0.7);
    }

    .nav-main-has-submenu:has(.nav-main-link.active)>.nav-main-link .nav-main-link-icon {
        color: #e11d48;
    }

    /* Header text yang lebih elegan */
    .content-header .text-dual {
        position: relative;
    }

    .content-header .bg-primary {
        background: #e11d48 !important;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Hilangkan duplikasi scrollbar style */
    #sidebar::-webkit-scrollbar {
        width: 6px;
    }

    #sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.932);
        border-radius: 10px;
    }

    #sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(225, 29, 72, 0.8);
    }

    /* Perbaikan spacing untuk item pertama dan terakhir */
    .nav-main-has-submenu ul li:first-child .nav-main-link {
        margin-top: 4px !important;
    }

    .nav-main-has-submenu ul li:last-child .nav-main-link {
        margin-bottom: 4px !important;
    }

    /* Style untuk submenu yang memiliki banyak item */
    .nav-main-has-submenu ul {
        padding-top: 8px !important;
        padding-bottom: 8px !important;
    }

    /* Perbaikan alignment icon dengan text */
    .nav-main-link {
        align-items: center;
    }

    .nav-main-link .nav-main-link-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 20px;
    }

    /* Style untuk disabled menu (jika ada) */
    .nav-main-link.disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    /* Tambahan efek untuk menu yang sedang di-hover */
    .nav-main-has-submenu>.nav-main-link:hover {
        background: rgba(255, 255, 255, 0.08);
    }

    /* Style untuk chevron yang lebih visible */
    .nav-main-link .fa-chevron-down {
        font-size: 12px;
        color: #94a3b8;
        margin-left: 8px;
        opacity: 0.8;
    }

    .nav-main-link:hover .fa-chevron-down {
        color: #e11d48;
        opacity: 1;
    }

    /* Style untuk submenu yang terbuka */
    .collapse.show {
        margin-bottom: 8px;
    }

    /* Perbaikan responsive untuk mobile */
    @media (max-width: 768px) {
        .nav-main-has-submenu ul .nav-main-link {
            padding-left: 35px !important;
        }

        .nav-main-has-submenu ul .nav-main-has-submenu ul .nav-main-link {
            padding-left: 50px !important;
        }
    }
</style>

<script>
    function getSidebarRoot() {
        return document.querySelector('#page-container > #sidebar') || document.querySelector('#sidebar');
    }

    function getSidebarScrollBase() {
        const sidebarRoot = getSidebarRoot();

        return sidebarRoot ? sidebarRoot.querySelector('.js-sidebar-scroll') : null;
    }

    function getSidebarScrollContainer() {
        if (
            window.One &&
            window.One._lSidebarScroll &&
            typeof window.One._lSidebarScroll.getScrollElement === 'function'
        ) {
            return window.One._lSidebarScroll.getScrollElement();
        }

        const sidebarScrollBase = getSidebarScrollBase();

        if (!sidebarScrollBase) {
            return null;
        }

        return sidebarScrollBase.querySelector('.simplebar-content-wrapper') || sidebarScrollBase;
    }

    function adjustSidebarHeight() {
        const sidebarRoot = getSidebarRoot();
        const header = sidebarRoot ? sidebarRoot.querySelector('.content-header') : null;
        const sidebarBody = getSidebarScrollBase();

        if (header && sidebarBody) {
            const headerHeight = header.offsetHeight;
            sidebarBody.style.height = `calc(100vh - ${headerHeight}px)`;

            if (
                window.One &&
                window.One._lSidebarScroll &&
                typeof window.One._lSidebarScroll.recalculate === 'function'
            ) {
                window.One._lSidebarScroll.recalculate();
            }
        }
    }

    function getActiveSidebarLink() {
        const sidebarRoot = getSidebarRoot();
        const activeLinks = sidebarRoot ? Array.from(sidebarRoot.querySelectorAll('.nav-main-link.active')) : [];

        return activeLinks.length ? activeLinks[activeLinks.length - 1] : null;
    }

    function getSidebarScrollStorageKey() {
        return 'keyone.wms.sidebar.scrollTop';
    }

    function saveSidebarScrollPosition() {
        const sidebarBodyScroll = getSidebarScrollContainer();

        if (sidebarBodyScroll) {
            sessionStorage.setItem(getSidebarScrollStorageKey(), String(sidebarBodyScroll.scrollTop));
        }
    }

    function restoreSidebarScrollPosition() {
        const sidebarBodyScroll = getSidebarScrollContainer();
        const savedScrollTop = sessionStorage.getItem(getSidebarScrollStorageKey());

        if (!sidebarBodyScroll || savedScrollTop === null) {
            return false;
        }

        sidebarBodyScroll.scrollTop = Number(savedScrollTop);

        return true;
    }

    function bindSidebarScrollPersistence() {
        const sidebarBodyScroll = getSidebarScrollContainer();
        const sidebarRoot = getSidebarRoot();

        if (sidebarBodyScroll && sidebarBodyScroll.dataset.keyoneScrollPersistence !== '1') {
            sidebarBodyScroll.dataset.keyoneScrollPersistence = '1';
            sidebarBodyScroll.addEventListener('scroll', function() {
                saveSidebarScrollPosition();
            }, {
                passive: true
            });
        }

        if (sidebarRoot && sidebarRoot.dataset.keyoneClickPersistence !== '1') {
            sidebarRoot.dataset.keyoneClickPersistence = '1';
            sidebarRoot.addEventListener('click', function(event) {
                const link = event.target.closest('a.nav-main-link');

                if (link && link.getAttribute('href') && link.getAttribute('href') !== '#') {
                    saveSidebarScrollPosition();
                }
            });
        }
    }

    function centerActiveSidebarLink(behavior = 'smooth') {
        const activeLink = getActiveSidebarLink();
        const sidebarBodyScroll = getSidebarScrollContainer();

        if (activeLink && sidebarBodyScroll) {
            const containerRect = sidebarBodyScroll.getBoundingClientRect();
            const linkRect = activeLink.getBoundingClientRect();
            const linkCenterFromContainerTop = linkRect.top - containerRect.top + (linkRect.height / 2);
            const targetTop = sidebarBodyScroll.scrollTop + linkCenterFromContainerTop - (sidebarBodyScroll
                .clientHeight / 2);

            sidebarBodyScroll.scrollTo({
                top: Math.max(targetTop, 0),
                behavior: behavior
            });
        }
    }

    function scheduleActiveSidebarCentering() {
        adjustSidebarHeight();
        bindSidebarScrollPersistence();

        if (!restoreSidebarScrollPosition()) {
            centerActiveSidebarLink('auto');
        }

        [100, 350, 700].forEach(function(delay) {
            setTimeout(function() {
                adjustSidebarHeight();
                bindSidebarScrollPersistence();

                if (!restoreSidebarScrollPosition()) {
                    centerActiveSidebarLink(delay === 700 ? 'smooth' : 'auto');
                }
            }, delay);
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        scheduleActiveSidebarCentering();

        // Sidebar Menu Search Filter
        const searchInput = document.getElementById('sidebar-menu-search');
        const clearBtn = document.getElementById('sidebar-menu-search-clear');
        const navMain = document.querySelector('#sidebar .nav-main');

        if (searchInput && navMain) {
            const initialOpenItems = new Set(navMain.querySelectorAll('.nav-main-item.open'));

            function performSearch() {
                const filter = searchInput.value.toLowerCase().trim();

                if (clearBtn) {
                    if (filter.length > 0) {
                        clearBtn.classList.remove('d-none');
                    } else {
                        clearBtn.classList.add('d-none');
                    }
                }

                const allItems = navMain.querySelectorAll('.nav-main-item');
                const allHeadings = navMain.querySelectorAll('.nav-main-heading');
                const allSubmenus = navMain.querySelectorAll('.nav-main-submenu');

                if (filter === '') {
                    allItems.forEach(function(item) {
                        item.style.display = '';
                        if (initialOpenItems.has(item)) {
                            item.classList.add('open');
                            const link = item.querySelector(':scope > .nav-main-link-submenu');
                            if (link) link.setAttribute('aria-expanded', 'true');
                        } else {
                            item.classList.remove('open');
                            const link = item.querySelector(':scope > .nav-main-link-submenu');
                            if (link) link.setAttribute('aria-expanded', 'false');
                        }
                    });
                    allHeadings.forEach(function(heading) {
                        heading.style.display = '';
                    });
                    allSubmenus.forEach(function(sub) {
                        sub.style.display = '';
                        sub.style.height = '';
                    });
                    return;
                }

                allHeadings.forEach(function(heading) {
                    heading.style.display = 'none';
                });
                allItems.forEach(function(item) {
                    item.style.display = 'none';
                    item.classList.remove('open');
                });
                allSubmenus.forEach(function(sub) {
                    sub.style.display = 'none';
                    sub.style.height = '';
                });

                allItems.forEach(function(item) {
                    const isDropdownTrigger = item.classList.contains('nav-main-link-submenu') || item
                        .querySelector(':scope > .nav-main-link-submenu');
                    const link = item.querySelector(':scope > .nav-main-link');
                    if (!link) return;

                    const linkName = link.querySelector('.nav-main-link-name') || link;
                    const itemPerm = item.getAttribute('data-permission') || '';
                    const linkPerm = link.getAttribute('data-permission') || '';
                    const href = link.getAttribute('href') || '';

                    const searchText = (linkName.textContent + ' ' + itemPerm + ' ' + linkPerm + ' ' +
                        href).toLowerCase();

                    if (!isDropdownTrigger && searchText.includes(filter)) {
                        item.style.display = '';

                        let curr = item.parentElement;
                        while (curr && curr !== navMain) {
                            if (curr.classList.contains('nav-main-submenu')) {
                                curr.style.display = 'block';
                                curr.style.height = 'auto';
                            }
                            if (curr.classList.contains('nav-main-item')) {
                                curr.style.display = '';
                                curr.classList.add('open');
                                const triggerLink = curr.querySelector(
                                    ':scope > .nav-main-link-submenu');
                                if (triggerLink) triggerLink.setAttribute('aria-expanded', 'true');
                            }
                            curr = curr.parentElement;
                        }
                    }
                });

                allItems.forEach(function(item) {
                    if (item.style.display !== 'none') {
                        let prev = item.previousElementSibling;
                        while (prev) {
                            if (prev.classList.contains('nav-main-heading')) {
                                prev.style.display = '';
                                break;
                            }
                            if (prev.classList.contains('nav-main-item') && prev.style.display !==
                                'none') {
                                break;
                            }
                            prev = prev.previousElementSibling;
                        }
                    }
                });
            }

            searchInput.addEventListener('input', performSearch);

            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    performSearch();
                    searchInput.focus();
                });
            }
        }

        window.addEventListener('resize', function() {
            adjustSidebarHeight();
            if (!restoreSidebarScrollPosition()) {
                centerActiveSidebarLink('auto');
            }
        });

        window.addEventListener('beforeunload', function() {
            saveSidebarScrollPosition();
        });
    });

    window.addEventListener('load', function() {
        scheduleActiveSidebarCentering();
    });
</script>
