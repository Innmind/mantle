<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Suspend\Strategy;
use Innmind\Immutable\Sequence;

final class Task
{
    private \Fiber $fiber;

    private function __construct(\Fiber $fiber)
    {
        $this->fiber = $fiber;
    }

    /**
     * @param callable(Suspend): void $task
     */
    public static function of(callable $task): self
    {
        return new self(new \Fiber($task));
    }

    /**
     * @param callable(): Strategy $strategy
     *
     * @return Sequence<self> Returns a Sequence for an easier integration in Forerunner
     */
    public function continue(callable $strategy): Sequence
    {
        if (!$this->fiber->isStarted()) {
            $this->fiber->start(Suspend::of($strategy()));

            return $this->next();
        }

        if (!$this->fiber->isTerminated()) {
            $this->fiber->resume();
        }

        return $this->next();
    }

    /**
     * @return Sequence<self>
     */
    private function next(): Sequence
    {
        return match (!$this->fiber->isTerminated()) {
            true => Sequence::of($this),
            false => Sequence::of(),
        };
    }
}
