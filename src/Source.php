<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Immutable\Maybe;

interface Source
{
    /**
     * @return Maybe<Task>
     */
    public function emerge(): Maybe;
    public function active(): bool;
}
