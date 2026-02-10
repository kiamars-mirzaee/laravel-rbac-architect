<?php

namespace Kiamars\RbacArchitect\Providers;

use Illuminate\Support\ServiceProvider;

class RbacServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');

        $router = $this->app['router'];
        $router->aliasMiddleware('rbac', \Kiamars\RbacArchitect\Middleware\ProtectByPermission::class);
    }

    public function register()
    {
    //
    }
}