<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};

/**
 * @template R
 */
final class Task
{
    private \Fiber $fiber;

    private function __construct(\Fiber $fiber)
    {
        $this->fiber = $fiber;
    }

    /**
     * @template A
     *
     * @param callable(OperatingSystem): A $task
     *
     * @return self<A>
     */
    public static function of(callable $task): self
    {
        return new self(new \Fiber($task));
    }

    public function continue(OperatingSystem $synchronous): mixed
    {
        $returned = null;

        if (!$this->fiber->isStarted()) {
            $suspend = Suspend::new();
            /** @var mixed */
            $returned = $this->fiber->start($synchronous->map(
                static fn($_, $config) => Factory::build(
                    $config
                        ->useStreamCapabilities(Asynchronous\Stream\Capabilities::of(
                            $suspend,
                            $config->streamCapabilities(),
                        ))
                        ->haltProcessVia(Asynchronous\Halt::of($suspend)),
                ),
            ));

            return $this->next($returned);
        }

        if (!$this->fiber->isTerminated()) {
            /** @var mixed */
            $returned = $this->fiber->resume();
        }

        return $this->next($returned);
    }

    public function resume(mixed $toSend): mixed
    {
        /** @var mixed */
        $returned = $this->fiber->resume($toSend);

        return $this->next($returned);
    }

    private function next(mixed $returned): mixed
    {
        if ($this->fiber->isTerminated()) {
            return $this->fiber->getReturn();
        }

        return $returned;
    }
}
