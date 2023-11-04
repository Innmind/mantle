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

    /**
     * @return R|Suspend\Action
     */
    public function continue(OperatingSystem $synchronous): mixed
    {
        if (!$this->fiber->isStarted()) {
            $suspend = Suspend::new();
            /** @var R|Suspend\Action */
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

        /** @var R|Suspend\Action */
        $returned = $this->fiber->resume();

        return $this->next($returned);
    }

    /**
     * @return R|Suspend\Action
     */
    public function resume(mixed $toSend): mixed
    {
        /** @var R|Suspend\Action */
        $returned = $this->fiber->resume($toSend);

        return $this->next($returned);
    }

    /**
     * @param R|Suspend\Action $returned
     *
     * @return R|Suspend\Action
     */
    private function next(mixed $returned): mixed
    {
        if ($this->fiber->isTerminated()) {
            /** @var R */
            return $this->fiber->getReturn();
        }

        return $returned;
    }
}
