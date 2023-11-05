<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};
use Innmind\TimeContinuum\Earth\{
    ElapsedPeriod,
    Period\Millisecond,
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
     * @internal
     *
     * @return R|Suspend\Action
     */
    public function start(OperatingSystem $synchronous): mixed
    {
        $suspend = Suspend::new();
        /** @var R|Suspend\Action */
        $returned = $this->fiber->start($synchronous->map(
            static fn($_, $config) => Factory::build(
                $config
                    ->useStreamCapabilities(Asynchronous\Stream\Capabilities::of(
                        $suspend,
                        $config->streamCapabilities(),
                    ))
                    ->haltProcessVia(Asynchronous\Halt::of($suspend))
                    ->withHttpHeartbeat(
                        ElapsedPeriod::of(10), // this is blocking the active task so it needs to be low
                        static fn() => $suspend(Suspend\Halt::of( // this allows to jump between tasks
                            Millisecond::of(1),
                        )),
                    ),
            ),
        ));

        return $this->next($returned);
    }

    /**
     * @internal
     *
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
