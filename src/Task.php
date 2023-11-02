<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};
use Innmind\Immutable\Sequence;

final class Task
{
    private \Fiber $fiber;

    private function __construct(\Fiber $fiber)
    {
        $this->fiber = $fiber;
    }

    /**
     * @param callable(OperatingSystem): void $task
     */
    public static function of(callable $task): self
    {
        return new self(new \Fiber($task));
    }

    /**
     * @return Sequence<self> Returns a Sequence for an easier integration in Forerunner
     */
    public function continue(OperatingSystem $synchronous): Sequence
    {
        if (!$this->fiber->isStarted()) {
            $suspend = Suspend::new();
            $this->fiber->start($synchronous->map(
                static fn($_, $config) => Factory::build(
                    $config
                        ->useStreamCapabilities(Asynchronous\Stream\Capabilities::of(
                            $suspend,
                            $config->streamCapabilities(),
                        ))
                        ->haltProcessVia(Asynchronous\Halt::of($suspend)),
                ),
            ));

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
