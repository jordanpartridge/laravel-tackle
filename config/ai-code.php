<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider
    |--------------------------------------------------------------------------
    |
    | The laravel/ai provider name to use. Must match a key in config/ai.php.
    | Defaults to 'anthropic' — set ANTHROPIC_API_KEY in your .env.
    |
    */
    'provider' => env('AI_CODE_PROVIDER', 'anthropic'),

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    |
    | The model used by the coding agent. Defaults to Claude Sonnet which
    | offers a good balance of capability and cost. Override via env.
    |
    */
    'model' => env('AI_CODE_MODEL', 'claude-sonnet-4-6'),

    /*
    |--------------------------------------------------------------------------
    | Max Steps
    |--------------------------------------------------------------------------
    |
    | Maximum number of tool-call / reasoning steps per agent turn. Prevents
    | runaway loops.
    |
    */
    'max_steps' => env('AI_CODE_MAX_STEPS', 40),

    /*
    |--------------------------------------------------------------------------
    | Budget (USD)
    |--------------------------------------------------------------------------
    |
    | Hard spend limit for the session. The agent will abort once the
    | estimated cost (tracked via token counts) exceeds this amount.
    |
    */
    'budget_usd' => env('AI_CODE_BUDGET', 1.00),

    /*
    |--------------------------------------------------------------------------
    | Shell Execution Policy
    |--------------------------------------------------------------------------
    |
    | Controls whether and how RunShell executes arbitrary commands.
    |
    |   off        - RunShell refuses everything. Use RunArtisan/RunTests.
    |   allowlist  - Only commands whose first token is in shell_allowlist run.
    |   approve    - Every command shows a confirmation prompt. (default)
    |   yolo       - Runs anything with no prompt. WARNING: dangerous.
    |
    */
    'shell' => env('AI_CODE_SHELL', 'approve'),

    'shell_allowlist' => [
        'composer',
        'npm',
        'php artisan',
    ],

    /*
    |--------------------------------------------------------------------------
    | Artisan Allowlist
    |--------------------------------------------------------------------------
    |
    | Glob patterns for Artisan commands the agent may run unattended via
    | RunArtisan. Commands not matching any pattern are refused.
    |
    */
    'artisan_allowlist' => [
        'make:*',
        'route:list',
        'migrate',
        'db:seed',
        'test',
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Paths
    |--------------------------------------------------------------------------
    |
    | Glob patterns (relative to workspace) that the agent can NEVER read or
    | write. This is the credential-exfiltration defense — do not weaken it.
    |
    */
    'protected_paths' => [
        '.env',
        '.env.*',
        'storage/*',
        'vendor/*',
        '.git/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Workspace
    |--------------------------------------------------------------------------
    |
    | The root directory the agent operates within. null defaults to the app's
    | base path. Set to an absolute path to restrict the agent further.
    |
    */
    'workspace' => null,

    /*
    |--------------------------------------------------------------------------
    | Memory / Persistence
    |--------------------------------------------------------------------------
    |
    |   none      - Ephemeral; each session starts fresh.
    |   file      - Persist transcript to storage/ai-code/*.json (default).
    |   database  - Use laravel/ai RemembersConversations (requires running
    |               the laravel/ai migrations first).
    |
    */
    'memory' => env('AI_CODE_MEMORY', 'file'),

];
