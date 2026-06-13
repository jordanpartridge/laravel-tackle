<?php

use Tackle\Support\CommandGuard;

it('allows commands matching the allowlist', function () {
    $guard = new CommandGuard();
    $allowlist = ['composer', 'npm', 'php artisan'];

    expect($guard->check('composer install', $allowlist))->toBeNull();
    expect($guard->check('npm run build', $allowlist))->toBeNull();
    expect($guard->check('php artisan migrate', $allowlist))->toBeNull();
});

it('refuses commands not in the allowlist', function () {
    $guard = new CommandGuard();
    $allowlist = ['composer', 'npm'];

    $result = $guard->check('rm -rf /', $allowlist);
    expect($result)->toBeString()->toContain('not in the allowlist');
});

it('allows glob patterns in the allowlist', function () {
    $guard = new CommandGuard();
    $allowlist = ['make:*', 'route:list', 'migrate'];

    expect($guard->check('make:model Post', $allowlist))->toBeNull();
    expect($guard->check('make:controller UserController', $allowlist))->toBeNull();
    expect($guard->check('route:list', $allowlist))->toBeNull();
});

it('refuses artisan commands not in artisan allowlist', function () {
    $guard = new CommandGuard();
    $allowlist = ['make:*', 'route:list', 'migrate'];

    $result = $guard->check('config:clear', $allowlist);
    expect($result)->toBeString()->toContain('not in the allowlist');
});
