<?php

namespace Tackle\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Process;
use Laravel\Ai\Tools\Request;
use Tackle\Support\CommandGuard;
use Tackle\Support\PathGuard;

class RunArtisan extends AbstractTool
{
    public function __construct(
        private PathGuard $pathGuard,
        private CommandGuard $commandGuard,
    ) {}

    public function description(): string
    {
        return 'Run an Artisan command as a subprocess. Only commands matching the artisan_allowlist in config may run. Returns stdout on success, or exit code + stderr on failure.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'command' => $schema->string()
                ->description('The Artisan command to run (without "php artisan" prefix), e.g. "make:model Post".')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $command = trim($request->string('command', ''));

        if ($command === '') {
            return 'A non-empty command is required.';
        }

        $allowlist = config('ai-code.artisan_allowlist', []);
        if ($refusal = $this->commandGuard->check($command, $allowlist)) {
            return $refusal;
        }

        $result = Process::path($this->pathGuard->workspace())
            ->run("php artisan {$command}");

        if ($result->failed()) {
            return "Artisan command failed (exit {$result->exitCode()}):\n{$result->errorOutput()}";
        }

        return $result->output() ?: "(Command ran successfully with no output.)";
    }
}
