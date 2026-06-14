<?php

use Laravel\Ai\Tools\Request;
use Tackle\Support\CommandGuard;
use Tackle\Support\PathGuard;
use Tackle\Tools\RunTests;

function makeRunTestsTool(): RunTests
{
    return new RunTests(new PathGuard(base_path()), app(CommandGuard::class));
}

afterEach(function () {
    @unlink(base_path('.env.testing'));
});

it('refuses when test is not in the allowlist for the current environment', function () {
    config()->set('tackle.artisan_allowlist', ['route:list']); // no 'test'

    $result = makeRunTestsTool()->handle(new Request([]));

    expect($result)->toContain('not in the allowlist');
});

it('refuses to run in production without .env.testing even when test is allowlisted', function () {
    config()->set('tackle.artisan_allowlist', ['route:list', 'test']);
    app()->detectEnvironment(fn () => 'production');
    @unlink(base_path('.env.testing'));

    $result = makeRunTestsTool()->handle(new Request([]));

    expect($result)->toContain('RunTests is disabled');

    app()->detectEnvironment(fn () => 'testing');
});

it('allows running in production when test is allowlisted and .env.testing exists', function () {
    config()->set('tackle.artisan_allowlist', ['route:list', 'test']);
    app()->detectEnvironment(fn () => 'production');
    file_put_contents(base_path('.env.testing'), 'APP_ENV=testing');

    $result = makeRunTestsTool()->handle(new Request([]));

    expect($result)->not->toContain('RunTests is disabled');
    expect($result)->not->toContain('not in the allowlist');

    app()->detectEnvironment(fn () => 'testing');
});

it('runs normally in non-production environments when test is allowlisted', function () {
    config()->set('tackle.artisan_allowlist', ['route:list', 'test']);

    $result = makeRunTestsTool()->handle(new Request([]));

    expect($result)->toBeString()
        ->not->toContain('RunTests is disabled')
        ->not->toContain('not in the allowlist');
});
