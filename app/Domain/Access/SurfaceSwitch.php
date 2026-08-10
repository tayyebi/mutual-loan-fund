<?php

namespace App\Domain\Access;

/**
 * A rendered link from the current surface into another one the actor holds.
 *
 * This is what lets a fund administrator move between "my money" and "running
 * the fund" without the two experiences bleeding into each other, and what lets
 * a system administrator who is also an investor keep the two roles separate.
 */
final class SurfaceSwitch
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $href,
        public readonly bool $current,
    ) {}
}
