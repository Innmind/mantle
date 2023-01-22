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
    private Sequence $threads;

    /**
     * @param Sequence<callable(Suspend): void> $threads
     */
    private function __construct(Sequence $threads)
    {
        $this->threads = $threads;
    }

    /**
     * @no-named-arguments
     *
     * @param callable(Suspend): void $threads
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
