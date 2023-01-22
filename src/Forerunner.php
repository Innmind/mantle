<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Continuation\{
    Strategy,
    Asynchronous,
};
use Innmind\Immutable\Sequence;

final class Forerunner
{
    /** @var callable(): Strategy */
    private $strategy;

    /**
     * @param callable(): Strategy $strategy
     */
    private function __construct(callable $strategy)
    {
        $this->strategy = $strategy;
    }

    public function __invoke(Source $source): void
    {
        /** @var Sequence<Task> */
        $threads = Sequence::of();

        while ($source->active() || !$threads->empty()) {
            $threads = $source->emerge()->match(
                static fn($thread) => ($threads)($thread),
                static fn() => $threads,
            );
            $threads = $threads->filter(fn($thread) => $thread->continue($this->strategy));
        }
    }

    /**
     * @param ?callable(): Strategy $strategy
     */
    public static function of(callable $strategy = null): self
    {
        return new self($strategy ?? Asynchronous::of(...));
    }
}
