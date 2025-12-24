<?php

namespace Botble\RealEstate\Providers;

use Botble\RealEstate\Commands\RenewPropertiesCommand;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            RenewPropertiesCommand::class,
            \Botble\RealEstate\Console\Commands\ProcessSavedSearchAlerts::class,
            \Botble\RealEstate\Console\Commands\SyncNigerianProperties::class,
        ]);
    }
}
