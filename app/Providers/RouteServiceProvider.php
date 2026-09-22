<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));


            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/dashboard.php'));

            // ACCOUNTING
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/accounting/journal.php'));

            //AMDIN
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/admin/sync.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/admin/user.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/admin/user_warehouse_mapping.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/admin/rev.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/admin/control_panel.php'));

            //WAREHOUSE
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/warehouse.php'));

            // INVENTORIES
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/inventory/part.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/inventory/qr_generator.php'));

            // PURCHASE
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/purchase/gr.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/purchase/dp.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/purchase/pr.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/purchase/pr_execute.php'));

            // SALES
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/sales/do.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/sales/do_execute.php'));

            // STOCK
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/stock/usage.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/stock/adjustment.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/stock/card.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/stock/opname.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/stock/transfer.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/stock/transfer_request.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/stock/transfer_execute.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/stock/transfer_receive.php'));
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/stock/stock_report.php'));


            // menu access
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/access.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
