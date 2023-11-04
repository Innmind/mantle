<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Source;

use Innmind\Mantle\{
    Source,
    Task,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * @implements Source<null>
 */
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

    public function __invoke(
        mixed $carry,
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        return $continuation
            ->launch($this->tasks->map(Task::of(...)))
            ->terminate();
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
}
