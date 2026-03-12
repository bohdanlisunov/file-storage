<?php
namespace App\Providers;

use App\Services\RabbitMQService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->singleton(RabbitMQService::class);
    }
    public function boot(): void {}
}
