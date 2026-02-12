<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\Agent;
use View;

class AgentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // create agent instance 
        $agent = new Agent();

        View::share('agent', $agent); // Not it will be accessible on the view 

        // Bind the value to the service container 
        $this->app->instance('agent', $agent);
    }
}
