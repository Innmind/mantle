<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Immutable\Sequence;

interface Source
{
    /**
     * @return Sequence<Task>
     */
    public function emerge(): Sequence;
    public function active(): bool;
}
