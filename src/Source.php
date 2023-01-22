<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Immutable\Sequence;

interface Source
{
    /**
     * @template C
     *
     * @param C $carry
     * @param Sequence<Task> $active
     *
     * @return array{C, Sequence<Task>}
     */
    public function emerge(mixed $carry, Sequence $active): array;
    public function active(): bool;
}
