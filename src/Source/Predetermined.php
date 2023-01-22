<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Source;

use Innmind\Mantle\{
    Source,
    Task,
    Continuation,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

final class Predetermined implements Source
{
    /** @var Sequence<callable(Continuation): void> */
    private Sequence $threads;

    /**
     * @param Sequence<callable(Continuation): void> $threads
     */
    private function __construct(Sequence $threads)
    {
        $this->threads = $threads;
    }

    /**
     * @no-named-arguments
     *
     * @param callable(Continuation): void $threads
     */
    public static function of(callable ...$threads): self
    {
        return new self(Sequence::of(...$threads));
    }

    public function emerge(): Maybe
    {
        $next = $this
            ->threads
            ->first()
            ->map(Task::of(...));
        $this->threads = $this->threads->drop(1);

        return $next;
    }

    public function active(): bool
    {
        return !$this->threads->empty();
    }
}
