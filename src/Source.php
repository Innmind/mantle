<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Immutable\Maybe;

interface Source
{
    /**
     * @return Maybe<Thread>
     */
    public function schedule(): Maybe;
    public function active(): bool;
}
