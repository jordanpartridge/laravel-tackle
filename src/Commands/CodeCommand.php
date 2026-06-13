<?php

namespace Tackle\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult;
use Tackle\Contracts\CodingAgent;
use Tackle\Support\BudgetTracker;

use function Laravel\Prompts\text;

class CodeCommand extends Command
{
    protected $signature = 'ai:code
        {--session= : Resume a named session}
        {--shell= : Override the shell mode for this session (off|allowlist|approve|yolo)}
        {--off : Shorthand for --shell=off}
        {--allowlist : Shorthand for --shell=allowlist}
        {--approve : Shorthand for --shell=approve}
        {--yolo : Shorthand for --shell=yolo}';

    protected $description = 'Start an interactive AI coding session powered by Laravel Tackle.';

    public function handle(CodingAgent $agent, BudgetTracker $budget): int
    {
        if (! App::runningInConsole()) {
            $this->error('ai:code must be run from the terminal.');
            return self::FAILURE;
        }

        if (! $this->isTty()) {
            $this->error('ai:code requires an interactive TTY — cannot run in a non-interactive pipe.');
            return self::FAILURE;
        }

        $shell = match (true) {
            (bool) $this->option('off')       => 'off',
            (bool) $this->option('allowlist') => 'allowlist',
            (bool) $this->option('approve')   => 'approve',
            (bool) $this->option('yolo')      => 'yolo',
            default                           => $this->option('shell'),
        };

        if ($shell !== null) {
            if (! in_array($shell, ['off', 'allowlist', 'approve', 'yolo'], strict: true)) {
                $this->error("Invalid --shell value '{$shell}'. Must be one of: off, allowlist, approve, yolo.");
                return self::FAILURE;
            }
            config(['ai-code.shell' => $shell]);
        }

        $this->renderBanner();

        while (true) {
            $task = text(
                label: 'What should I work on?',
                placeholder: 'Describe a task, or type "exit" to quit.',
                required: true,
            );

            if (in_array(strtolower(trim($task)), ['exit', 'quit', 'q'], strict: true)) {
                $this->line('');
                $this->line($budget->summary());
                $this->line('Goodbye!');
                return self::SUCCESS;
            }

            if ($budget->overBudget()) {
                $this->error(sprintf(
                    'Session aborted: estimated cost ($%.4f) exceeds the budget limit ($%.2f).',
                    $budget->estimatedCost(),
                    $budget->budgetUsd(),
                ));
                return self::FAILURE;
            }

            $this->line('');
            $this->line('<fg=yellow>● Agent is working...</>');
            $this->line('');

            try {
                $this->runAgentTurn($agent, $budget, $task);
            } catch (\Throwable $e) {
                $this->error('Agent error: ' . $e->getMessage());
                $this->line('The session is still active. You can continue with a new task.');
            }

            $this->showGitDiff();

            $this->line('');
            $this->line('<fg=gray>Tip: run `git diff` to review changes, `git checkout -- .` to discard them.</>');
            $this->line('');
        }
    }

    private function runAgentTurn(CodingAgent $agent, BudgetTracker $budget, string $task): void
    {
        $response = $agent->stream($task);

        $response->each(function ($event) use ($budget) {
            if ($event instanceof TextDelta) {
                $this->output->write($event->delta);
                return;
            }

            if ($event instanceof ToolCall) {
                $this->renderToolCall($event);
                return;
            }

            if ($event instanceof ToolResult) {
                $this->renderToolResult($event);
                return;
            }

            if ($event instanceof StreamEnd) {
                $budget->record($event->usage->promptTokens, $event->usage->completionTokens);

                if ($budget->overBudget()) {
                    $this->newLine();
                    $this->error(sprintf(
                        'Budget limit reached ($%.4f / $%.2f). Stopping.',
                        $budget->estimatedCost(),
                        $budget->budgetUsd(),
                    ));
                }
            }
        });

        $this->newLine();
    }

    private function renderToolCall(ToolCall $event): void
    {
        $tool = $event->toolCall->name;
        $args = $event->toolCall->arguments;

        $summary = match ($tool) {
            'ReadFile'   => "reading " . ($args['path'] ?? '?'),
            'Glob'       => "listing " . ($args['pattern'] ?? '?'),
            'SearchCode' => "searching for " . ($args['query'] ?? '?'),
            'EditFile'   => "editing " . ($args['path'] ?? '?'),
            'WriteFile'  => "creating " . ($args['path'] ?? '?'),
            'RunArtisan' => "artisan " . ($args['command'] ?? '?'),
            'RunTests'   => "running tests",
            'RunPint'    => "running pint",
            'RunShell'   => "shell: " . ($args['command'] ?? '?'),
            default      => $tool,
        };

        $this->newLine();
        $this->line("<fg=cyan>→ {$summary}</>");
    }

    private function renderToolResult(ToolResult $event): void
    {
        $tool   = $event->toolResult->name;
        $result = (string) ($event->toolResult->result ?? '');

        if (in_array($tool, ['RunTests', 'RunArtisan', 'RunShell'], strict: true)) {
            if (str_contains($result, 'FAILED') || str_contains($result, 'Error')) {
                $this->line('<fg=red>  ✗ Command reported failures — agent will handle them.</>');
            }
        }
    }

    private function showGitDiff(): void
    {
        if (! is_dir(base_path('.git'))) {
            return;
        }

        $output = shell_exec('git diff --stat 2>/dev/null');

        if ($output && trim($output) !== '') {
            $this->line('');
            $this->line('<fg=green>Git diff stat:</>');
            $this->line(trim($output));
        }
    }

    private function isTty(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(STDIN);
    }

    private function renderBanner(): void
    {
        $model  = config('ai-code.model', 'claude-sonnet-4-6');
        $budget = config('ai-code.budget_usd', 1.00);
        $shell  = config('ai-code.shell', 'approve');

        $this->line('');
        $this->line('<fg=green;options=bold>Laravel Tackle — AI Coding Assistant</>');
        $this->line("<fg=gray>Model: {$model} | Budget: \${$budget} | Shell: {$shell}</>");
        $this->line('<fg=gray>Type "exit" to quit. All edits are unstaged — use git to review or discard them.</>');
        $this->line('');
    }
}
