<?php

namespace Tackle\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tackle\TackleServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TackleServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('ai-code.workspace', sys_get_temp_dir() . '/tackle-tests');
        config()->set('ai-code.protected_paths', ['.env', '.env.*', 'storage/*', 'vendor/*', '.git/*']);
        config()->set('ai-code.shell', 'off');
        config()->set('ai-code.artisan_allowlist', ['make:*', 'route:list', 'migrate', 'test']);
        config()->set('ai-code.shell_allowlist', ['composer', 'npm', 'php artisan']);
        config()->set('ai-code.budget_usd', 1.00);
    }
}
