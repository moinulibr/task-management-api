<?php
namespace App\Providers;

use App\Interfaces\TaskInterface;
use App\Repositories\TaskRepository;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider{
    /**
     * Register services.
     */
    public function register(): void
    {
        //it's not using. using AppServiceProvider
        //Task Binding
        $this->app->bind(
            TaskInterface::class,
            TaskRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
