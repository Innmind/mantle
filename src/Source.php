<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Immutable\Sequence;

interface Source
{
    /**
     * @param Sequence<Task> $active
     *
     * @return Sequence<Task>
     */
    public function emerge(Sequence $active): Sequence;
    public function active(): bool;
}
