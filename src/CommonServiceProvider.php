<?php

namespace Ijiabao\Laravel;

use Illuminate\Support\ServiceProvider;
use Ijiabao\Laravel\Middleware\HandViewPaths;

class CommonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        \Schema::defaultStringLength(191);
        \DB::enableQueryLog();

        if(config('app.debug', false)) {
            ini_set('display_errors', 'On');
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        }
        else {
            ini_set('display_errors', 'Off');
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
        }

        // 发布配置
        $this->publishes([
             __DIR__.'/../publish/config/ijiabao.php' => config_path('ijiabao.php')
        ], 'config');

        // 修改视图路径(运行时)
        if(config('ijiabao.hand_view_path', true)){
            /** @var \Illuminate\Routing\Router $router */
            $router = $this->app['router'];
            // $router->aliasMiddleware('viewpath', HandViewPaths::class);
            $router->pushMiddlewareToGroup('web', HandViewPaths::class);
        }

        // Artisan 命令注册
        $this->commands([
            \Ijiabao\Laravel\Console\DbDumpCommand::class,
            \Ijiabao\Laravel\Console\InitCommand::class,
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->mergeConfigFrom(
            __DIR__.'/../publish/config/ijiabao.php', 'ijiabao'
        );
    }
}