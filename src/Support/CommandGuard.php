<?php

namespace Tackle\Support;

class CommandGuard
{
    /**
     * Returns null if the command is allowed by the given allowlist.
     * Returns a refusal message if it is not.
     *
     * @param  list<string>  $allowlist  Glob patterns or literal prefixes.
     */
    public function check(string $command, array $allowlist): ?string
    {
        $command = trim($command);

        foreach ($allowlist as $pattern) {
            // Exact or glob match against the full command.
            if (fnmatch($pattern, $command, FNM_CASEFOLD)) {
                return null;
            }

            // Prefix match: e.g. "php artisan" allows "php artisan migrate --force".
            if (str_starts_with($command, rtrim($pattern, '*'))) {
                return null;
            }
        }

        return "Command '{$command}' is not in the allowlist. Allowed patterns: "
            . implode(', ', $allowlist);
    }
}
