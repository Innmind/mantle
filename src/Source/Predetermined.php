<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Source;

use Innmind\Mantle\{
    Source,
    Task,
    Suspend,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

final class Predetermined implements Source
{
    /** @var Sequence<callable(Suspend): void> */
    private Sequence $tasks;

    /**
     * @param Sequence<callable(Suspend): void> $tasks
     */
    private function __construct(Sequence $tasks)
    {
        $this->tasks = $tasks;
    }

    /**
     * @no-named-arguments
     *
     * @param callable(Suspend): void $tasks
     */
    public static function of(callable ...$tasks): self
    {
        return new self(Sequence::of(...$tasks));
    }

    public function emerge(Sequence $active): Sequence
    {
        $next = $this->tasks->map(Task::of(...));
        $this->tasks = $this->tasks->clear();

        return $next;
    }

    public function active(): bool
    {
        return !$this->tasks->empty();
    }
}
