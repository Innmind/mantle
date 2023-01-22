<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Continuation\Strategy;

final class Task
{
    private \Fiber $fiber;

    private function __construct(\Fiber $fiber)
    {
        $this->fiber = $fiber;
    }

    /**
     * @param callable(Continuation): void $thread
     */
    public static function of(callable $thread): self
    {
        return new self(new \Fiber($thread));
    }

    /**
     * @param callable(): Strategy $strategy
     */
    public function continue(callable $strategy): bool
    {
        if (!$this->fiber->isStarted()) {
            $this->fiber->start(Continuation::of($strategy()));
        }

        if (!$this->fiber->isTerminated()) {
            $this->fiber->resume();
        }

        return !$this->fiber->isTerminated();
    }
}
