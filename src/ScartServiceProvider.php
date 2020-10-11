<?php

namespace S-Cart\Core;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use S-Cart\Core\Front\Models\ShopProduct;
use S-Cart\Core\Front\Models\ShopCategory;
use S-Cart\Core\Front\Models\ShopBanner;
use S-Cart\Core\Front\Models\ShopBrand;
use S-Cart\Core\Front\Models\ShopSupplier;
use S-Cart\Core\Front\Models\ShopNews;
use S-Cart\Core\Front\Models\ShopPage;
use S-Cart\Core\Front\Models\ShopStore;
use S-Cart\Core\Commands\Backup;
use S-Cart\Core\Commands\MakePlugin;
class ScartServiceProvider extends ServiceProvider
{
    protected $commands = [
        Backup::class,
        MakePlugin::class,
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/Config/s-cart.php', 's-cart');
        $this->mergeConfigFrom(__DIR__.'/Config/lfm.php', 'lfm');

        // $this->loadTranslationsFrom(__DIR__.'/lang', 'laravel-filemanager');

        $this->loadViewsFrom(__DIR__.'/Admin/Views', 's-cart');

        $this->registerPublishing();
        
    if(!file_exists(public_path('install.php'))) {
        foreach (glob(__DIR__.'/Library/Helpers/*.php') as $filename) {
            require_once $filename;
        }
        foreach (glob(app_path() . '/Library/Helpers/*.php') as $filename) {
            require_once $filename;
        }

        foreach (glob(app_path() . '/Plugins/*/*/Provider.php') as $filename) {
            require_once $filename;
        }

        $this->bootScart();

        //Route Admin
        if (file_exists($routes = __DIR__.'/Admin/routes.php')) {
            $this->loadRoutesFrom($routes);
        }

        //Route Api
        if (file_exists($routes = __DIR__.'/Api/routes.php')) {
            $this->loadRoutesFrom($routes);
        }

        //Route Front
        if (file_exists($routes = __DIR__.'/Front/routes.php')) {
            $this->loadRoutesFrom($routes);
        }

        try {
            DB::connection(SC_CONNECTION)->getPdo();
        } catch(\Throwable $e) {
            sc_report($e->getMessage());
            return;
        }
    }

        $this->validationExtend();

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if (file_exists(__DIR__.'/Library/Const.php')) {
            require_once (__DIR__.'/Library/Const.php');
        }
        $this->app->bind('cart', '\S-Cart\Core\Library\ShoppingCart\Cart');
        
        $this->registerRouteMiddleware();

        $this->commands($this->commands);
    }

    public function bootScart()
    {
        //Check domain exist
        $storeId = 1;

        //Process for multi store
        if(sc_config_global('MultiStorePro')) {
            $domain = sc_process_domain_store(url('/'));
            $arrDomain = ShopStore::getDomainActive();
            if (in_array($domain, $arrDomain)) {
                $storeId =  array_search($domain, $arrDomain);
            }
        }
        //End process multi store

        //Get storeId
        config(['app.storeId' => $storeId]);
        if (sc_config('LOG_SLACK_WEBHOOK_URL')) {
            config(['logging.channels.slack.url' => sc_config('LOG_SLACK_WEBHOOK_URL')]);
        }

        config(['app.name' => sc_store('title')]);

        //Config for  email
        config(['mail.default' => 'smtp']);
        
        $smtpHost     = sc_config('smtp_host');
        $smtpPort     = sc_config('smtp_port');
        $smtpSecurity = sc_config('smtp_security');
        $smtpUser     = sc_config('smtp_user');
        $smtpPassword = sc_config('smtp_password');
        config(['mail.mailers.smtp.host' => $smtpHost]);
        config(['mail.mailers.smtp.port' => $smtpPort]);
        config(['mail.mailers.smtp.encryption' => $smtpSecurity]);
        config(['mail.mailers.smtp.username' => $smtpUser]);
        config(['mail.mailers.smtp.password' => $smtpPassword]);

        config(
            [
                'mail.from.address' => sc_store('email'),
                'mail.from.name' => sc_store('title')
            ]
        );
        //email

        // Time zone
        config(['app.timezone' => (sc_store('timezone') ?? config('app.timezone'))]);
        // End time zone

        //Share variable for view
        view()->share('sc_languages', sc_language_all());
        view()->share('sc_currencies', sc_currency_all());
        view()->share('sc_blocksContent', sc_store_block());
        view()->share('sc_layoutsUrl', sc_link());
        view()->share('sc_templatePath', 'templates.' . sc_store('template'));
        view()->share('sc_templateFile', 'templates/' . sc_store('template'));
        //variable model
        view()->share('modelProduct', (new ShopProduct));
        view()->share('modelCategory', (new ShopCategory));
        view()->share('modelBanner', (new ShopBanner));
        view()->share('modelBrand', (new ShopBrand));
        view()->share('modelSupplier', (new ShopSupplier));
        view()->share('modelNews', (new ShopNews));
        view()->share('modelPage', (new ShopPage));
        //
        view()->share('templatePathAdmin', (config('admin.custom') ? 'admin.': 's-cart::'));


    }

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'localization'   => Front\Middleware\Localization::class,
        'currency'       => Front\Middleware\Currency::class,
        'api.connection' => Api\Middleware\ApiConnection::class,
        'checkdomain'    => Front\Middleware\CheckDomain::class,
        'json.response' => Api\Middleware\ForceJsonResponse::class,
        //Admin
        'admin.auth' => Admin\Middleware\Authenticate::class,
        'admin.log' => Admin\Middleware\LogOperation::class,
        'admin.permission' => Admin\Middleware\PermissionMiddleware::class,
        'admin.theme' => Admin\Middleware\AdminTheme::class,
        'admin.storeId' => Admin\Middleware\AdminStoreId::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'admin' => [
            'admin.auth',
            'admin.log',
            'admin.permission',
            'admin.theme',
        ],
    ];

    /**
     * Register the route middleware.
     *
     * @return void
     */
    protected function registerRouteMiddleware()
    {
        // register route middleware.
        foreach ($this->routeMiddleware as $key => $middleware) {
            app('router')->aliasMiddleware($key, $middleware);
        }

        // register middleware group.
        foreach ($this->middlewareGroups as $key => $middleware) {
            app('router')->middlewareGroup($key, $middleware);
        }
    }


    /**
     * Validattion extend
     *
     * @return  [type]  [return description]
     */
    protected function validationExtend() {
        Validator::extend('product_sku_unique', function ($attribute, $value, $parameters, $validator) {
            $productId = $parameters[0] ?? '';
            return (new Admin\Models\AdminProduct)
                ->checkProductValidationAdmin('sku', $value, $productId, session('adminStoreId'));
        });

        Validator::extend('product_alias_unique', function ($attribute, $value, $parameters, $validator) {
            $productId = $parameters[0] ?? '';
            return (new Admin\Models\AdminProduct)
            ->checkProductValidationAdmin('alias', $value, $productId, session('adminStoreId'));
        });

    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {

        // $this->publishes([
        //     __DIR__.'/../public' => public_path('vendor/laravel-filemanager'),
        // ], 'lfm_public');

        $this->publishes([
            __DIR__.'/Admin/Views'  => base_path('resources/views/admin'),
        ], 's-cart-view');

        // $this->publishes([
        //     __DIR__.'/Handlers/LfmConfigHandler.php' => base_path('app/Handlers/LfmConfigHandler.php'),
        // ], 'lfm_handler');
        }
    }
}
