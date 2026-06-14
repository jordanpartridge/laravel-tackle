<?php

namespace Tackle\Attributes;

use Attribute;

/**
 * Controls whether Tackle's self-healer will attempt to fix a failing job class.
 *
 * Usage:
 *   #[Healable(false)]
 *   class MyJob implements ShouldQueue { ... }
 *
 * Omitting the attribute (or passing true) opts the job in — healing is enabled by default.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Healable
{
    public function __construct(public readonly bool $enabled = true) {}
}
