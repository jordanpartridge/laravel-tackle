<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Laravel\Ai\Tools\Request;
use Tackle\Support\GitHubClient;
use Tackle\Tools\CreatePullRequest;

function makePrTool(): CreatePullRequest
{
    return new CreatePullRequest(app(GitHubClient::class));
}

// ---------------------------------------------------------------------------
// Not configured
// ---------------------------------------------------------------------------

it('returns not-configured message when GitHub credentials are missing', function () {
    config()->set('ai-code.github.token', null);
    config()->set('ai-code.github.repo', null);

    Process::fake([
        'gh*' => Process::result('', '', 1),
    ]);

    $result = makePrTool()->handle(new Request([
        'title'  => 'Fix login',
        'body'   => 'Fixed the login flow.',
        'branch' => 'tackle/issue-3-fix-login',
    ]));

    expect($result)->toContain('GITHUB_TOKEN');
});

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

it('returns error when title is missing', function () {
    config()->set('ai-code.github.token', 'ghp_token');
    config()->set('ai-code.github.repo', 'acme/app');

    $result = makePrTool()->handle(new Request([
        'body'   => 'Some body.',
        'branch' => 'tackle/fix',
    ]));

    expect($result)->toContain('required');
});

it('returns error when no changes to commit', function () {
    config()->set('ai-code.github.token', 'ghp_token');
    config()->set('ai-code.github.repo', 'acme/app');

    Process::fake([
        'git status*' => Process::result('', '', 0),
    ]);

    $result = makePrTool()->handle(new Request([
        'title'  => 'Fix login',
        'body'   => 'Fixed.',
        'branch' => 'tackle/fix',
    ]));

    expect($result)->toContain('No changes to commit');
});

// ---------------------------------------------------------------------------
// Happy path
// ---------------------------------------------------------------------------

it('creates branch, commits, pushes, and opens a PR', function () {
    config()->set('ai-code.github.token', 'ghp_token');
    config()->set('ai-code.github.repo', 'acme/app');

    Process::fake([
        'git status*'   => Process::result(' M app/Foo.php', '', 0),
        'git checkout*' => Process::result('', '', 0),
        'git add*'      => Process::result('', '', 0),
        'git commit*'   => Process::result('[tackle/issue-3-fix abc1234] Fix issue 3', '', 0),
        'git push*'     => Process::result('', '', 0),
    ]);

    Http::fake([
        '*api.github.com*' => Http::response([
            'html_url' => 'https://github.com/acme/app/pull/99',
            'number'   => 99,
        ], 201),
    ]);

    $result = makePrTool()->handle(new Request([
        'title'        => 'Fix issue 3',
        'body'         => 'Implemented the fix.',
        'branch'       => 'tackle/issue-3-fix',
        'base'         => 'main',
        'issue_number' => 3,
    ]));

    expect($result)->toContain('https://github.com/acme/app/pull/99');
});

it('appends Closes #N to PR body when issue_number is given', function () {
    config()->set('ai-code.github.token', 'ghp_token');
    config()->set('ai-code.github.repo', 'acme/app');

    Process::fake([
        'git status*'   => Process::result(' M app/Foo.php', '', 0),
        'git checkout*' => Process::result('', '', 0),
        'git add*'      => Process::result('', '', 0),
        'git commit*'   => Process::result('', '', 0),
        'git push*'     => Process::result('', '', 0),
    ]);

    Http::fake([
        '*api.github.com*' => Http::response(['html_url' => 'https://github.com/acme/app/pull/10'], 201),
    ]);

    makePrTool()->handle(new Request([
        'title'        => 'My fix',
        'body'         => 'Details here.',
        'branch'       => 'tackle/issue-5',
        'issue_number' => 5,
    ]));

    Http::assertSent(fn ($request) => str_contains($request->body(), 'Closes #5'));
});

it('returns error when GitHub API rejects the PR', function () {
    config()->set('ai-code.github.token', 'ghp_token');
    config()->set('ai-code.github.repo', 'acme/app');

    Process::fake([
        'git status*'   => Process::result(' M app/Foo.php', '', 0),
        'git checkout*' => Process::result('', '', 0),
        'git add*'      => Process::result('', '', 0),
        'git commit*'   => Process::result('', '', 0),
        'git push*'     => Process::result('', '', 0),
    ]);

    Http::fake([
        '*api.github.com*' => Http::response(['message' => 'Validation Failed'], 422),
    ]);

    $result = makePrTool()->handle(new Request([
        'title'  => 'Fix',
        'body'   => 'Details.',
        'branch' => 'tackle/fix',
    ]));

    expect($result)->toContain('Validation Failed');
});

it('returns error when git checkout fails', function () {
    config()->set('ai-code.github.token', 'ghp_token');
    config()->set('ai-code.github.repo', 'acme/app');

    Process::fake([
        'git status*'   => Process::result(' M app/Foo.php', '', 0),
        'git checkout*' => Process::result('', 'fatal: branch already exists', 1),
    ]);

    $result = makePrTool()->handle(new Request([
        'title'  => 'Fix',
        'body'   => 'Details.',
        'branch' => 'tackle/fix',
    ]));

    expect($result)->toContain('Failed to create branch');
});
