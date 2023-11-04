<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    Earth\Period\Millisecond,
};
use Innmind\Stream\Watch\Ready;
use Innmind\Immutable\{
    Sequence,
    Set,
    Maybe,
};

/**
 * @internal
 */
final class Wait
{
    private OperatingSystem $os;
    /** @var Sequence<Task\PendingActivity> */
    private Sequence $tasks;

    /**
     * @param Sequence<Task\PendingActivity> $tasks
     */
    private function __construct(
        OperatingSystem $os,
        Sequence $tasks,
    ) {
        $this->os = $os;
        $this->tasks = $tasks;
    }

    /**
     * @return Sequence<Task\PendingActivity|Task\Activated>
     */
    public function __invoke(): Sequence
    {
        $started = $this->os->clock()->now();

        /** @var Maybe<ElapsedPeriod> */
        $shortestTimeout = Maybe::nothing();
        $shortestTimeout = $this
            ->tasks
            ->map(static fn($task) => $task->action()->timeout())
            ->reduce(
                $shortestTimeout,
                static fn(Maybe $shortestTimeout, Maybe $timeout) => $shortestTimeout
                    ->flatMap(static fn(ElapsedPeriod $a) => $timeout->map(
                        static fn(ElapsedPeriod $b) => match ($a->longerThan($b)) {
                            true => $b,
                            false => $a,
                        },
                    ))
                    ->otherwise(static fn() => $timeout),
            );
        $forRead = $this
            ->tasks
            ->map(static fn($task) => $task->action()->forRead())
            ->toSet()
            ->flatMap(static fn($streams) => $streams);
        $forWrite = $this
            ->tasks
            ->map(static fn($task) => $task->action()->forWrite())
            ->toSet()
            ->flatMap(static fn($streams) => $streams);

        if ($forRead->empty() && $forWrite->empty()) {
            $_ = $shortestTimeout->match(
                fn($shortestTimeout) => $this
                    ->os
                    ->process()
                    ->halt(new Millisecond($shortestTimeout->milliseconds())),
                static fn() => null,
            );
            $took = $this->os->clock()->now()->elapsedSince($started);

            return $this->continue($took, Maybe::just(new Ready(
                Set::of(),
                Set::of(),
            )));
        }

        $timeout = $shortestTimeout->match(
            static fn($timeout) => $timeout,
            static fn() => null,
        );
        /**
         * @psalm-suppress TooFewArguments
         * @psalm-suppress InvalidArgument
         */
        $watch = $this
            ->os
            ->sockets()
            ->watch($timeout)
            ->forRead(...$forRead()->toList())
            ->forWrite(...$forWrite()->toList());

        $ready = $watch();
        $took = $this->os->clock()->now()->elapsedSince($started);

        return $this->continue(
            $took,
            $ready,
        );
    }

    public static function new(OperatingSystem $os): self
    {
        return new self($os, Sequence::of());
    }

    public function with(Task\PendingActivity $task): self
    {
        return new self($this->os, $this->tasks->add($task));
    }

    /**
     * @param Maybe<Ready> $ready
     *
     * @return Sequence<Task\PendingActivity|Task\Activated>
     */
    private function continue(
        ElapsedPeriod $took,
        Maybe $ready,
    ): Sequence {
        return $this->tasks->map(static fn($task) => $task->continue(
            $took,
            $ready,
        ));
    }
}
