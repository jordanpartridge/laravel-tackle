<?php

namespace Tackle;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tackle\Agents\DefaultCodingAgent;
use Tackle\Commands\CodeCommand;
use Tackle\Contracts\CodingAgent;

class TackleServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-tackle')
            ->hasConfigFile('ai-code')
            ->hasCommand(CodeCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->bind(CodingAgent::class, DefaultCodingAgent::class);
    }
}
