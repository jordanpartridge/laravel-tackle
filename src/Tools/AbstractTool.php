<?php

namespace Tackle\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Base class for all Tackle tools. Adjusting the laravel/ai Tool interface
 * import path is the only change needed if upstream renames it.
 */
abstract class AbstractTool implements Tool
{
    abstract public function description(): Stringable|string;

    abstract public function schema(JsonSchema $schema): array;

    abstract public function handle(Request $request): Stringable|string;
}
