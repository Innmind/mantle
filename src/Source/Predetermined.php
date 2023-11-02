<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Source;

use Innmind\Mantle\{
    Source,
    Task,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

final class Predetermined implements Source
{
    /** @var Sequence<callable(OperatingSystem): void> */
    private Sequence $tasks;

    /**
     * @param Sequence<callable(OperatingSystem): void> $tasks
     */
    private function __construct(Sequence $tasks)
    {
        $this->tasks = $tasks;
    }

    /**
     * @no-named-arguments
     *
     * @param callable(OperatingSystem): void $tasks
     */
    public static function of(callable ...$tasks): self
    {
        return new self(Sequence::of(...$tasks));
    }

    public function emerge(mixed $carry, Sequence $active): array
    {
        $next = $this->tasks->map(Task::of(...));
        $this->tasks = $this->tasks->clear();

        return [$carry, $next];
    }

    public function active(): bool
    {
        return !$this->tasks->empty();
    }
}
