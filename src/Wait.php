<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Source\Context;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    Earth\Period\Millisecond,
};
use Innmind\Stream\{
    Stream,
    Watch\Ready,
};
use Innmind\Immutable\{
    Sequence,
    Set,
    Maybe,
};

/**
 * @internal
 * @template C
 * @template R
 */
final class Wait
{
    private OperatingSystem $os;
    /** @var Maybe<Task\PendingActivity<Context<C, R>>> */
    private Maybe $source;
    /** @var Sequence<Task\PendingActivity<R>> */
    private Sequence $tasks;

    /**
     * @param Maybe<Task\PendingActivity<Context<C, R>>> $source
     * @param Sequence<Task\PendingActivity<R>> $tasks
     */
    private function __construct(
        OperatingSystem $os,
        Maybe $source,
        Sequence $tasks,
    ) {
        $this->os = $os;
        $this->source = $source;
        $this->tasks = $tasks;
    }

    /**
     * @return array{
     *     Maybe<Task\PendingActivity<Context<C, R>>|Task\Activated<Context<C, R>>>,
     *     Sequence<Task\PendingActivity<R>|Task\Activated<R>>
     * }
     */
    public function __invoke(): array
    {
        $started = $this->os->clock()->now();

        $shortestTimeout = $this
            ->source
            ->flatMap(static fn($source) => $source->action()->timeout());
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
            ->flatMap(static fn($streams) => $streams)
            ->merge(
                $this
                    ->source
                    ->toSequence()
                    ->toSet()
                    ->flatMap(static fn($task) => $task->action()->forRead()),
            );
        $forWrite = $this
            ->tasks
            ->map(static fn($task) => $task->action()->forWrite())
            ->toSet()
            ->flatMap(static fn($streams) => $streams)
            ->merge(
                $this
                    ->source
                    ->toSequence()
                    ->toSet()
                    ->flatMap(static fn($task) => $task->action()->forWrite()),
            );

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
        /** @psalm-suppress InvalidArgument */
        $watch = $this->os->sockets()->watch($timeout);
        $watch = $forRead->sort(fn($a, $b) => 0)->match(
            static fn($read, $rest) => $watch->forRead($read, ...$rest->toList()),
            static fn() => $watch,
        );
        $watch = $forWrite->sort(fn($a, $b) => 0)->match(
            static fn($write, $rest) => $watch->forWrite($write, ...$rest->toList()),
            static fn() => $watch,
        );

        // File streams are watched to allow walking over them asynchronously
        // between tasks. Technically a file is always readable from PHP point
        // of view. However when multiple files are watched via `stream_select`
        // even if a file as reached the end before another one it will wait
        // before all of them reach the end to flag all of them as EOF at the
        // same time. The problem with this behaviour is that essentially it
        // synchronizes all tasks, thus losing the advantage of running them
        // concurrently.
        // To circumvent this we add manually all file streams that have no data
        // left to read, thus the target code will read the stream and they'll
        // be flagged as EOF.
        // This hack SHOULD NOT pose any side effects, however if you land here
        // it may mean it does. In such case I have no idea how to fix it :/
        $ready = $watch()->map(static fn($ready) => new Ready(
            $ready->toRead()->merge($forRead->filter(self::isFile(...))),
            $ready->toWrite(),
        ));
        $took = $this->os->clock()->now()->elapsedSince($started);

        return $this->continue(
            $took,
            $ready,
        );
    }

    public static function new(OperatingSystem $os): self
    {
        /** @var Maybe<Task\PendingActivity<Context<mixed, mixed>>> */
        $source = Maybe::nothing();

        return new self($os, $source, Sequence::of());
    }

    /**
     * @template A
     * @template B
     *
     * @param Maybe<Task\PendingActivity<Context<A, B>>> $source
     *
     * @return self<A, B>
     */
    public function withSource(Maybe $source): self
    {
        /** @psalm-suppress InvalidArgument Force erase the type on purpose */
        return new self($this->os, $source, $this->tasks);
    }

    /**
     * @param Task\PendingActivity<R> $task
     *
     * @return self<C, R>
     */
    public function with(Task\PendingActivity $task): self
    {
        return new self($this->os, $this->source, $this->tasks->add($task));
    }

    /**
     * @param Maybe<Ready> $ready
     *
     * @return array{
     *     Maybe<Task\PendingActivity<Context<C, R>>|Task\Activated<Context<C, R>>>,
     *     Sequence<Task\PendingActivity<R>|Task\Activated<R>>
     * }
     */
    private function continue(
        ElapsedPeriod $took,
        Maybe $ready,
    ): array {
        $source = $this->source->map(static fn($source) => $source->continue(
            $took,
            $ready,
        ));
        $tasks = $this->tasks->map(static fn($task) => $task->continue(
            $took,
            $ready,
        ));

        return [$source, $tasks];
    }

    private static function isFile(Stream $stream): bool
    {
        $meta = \stream_get_meta_data($stream->resource());

        // stdin, stdout and stderr are not seekable
        return $meta['seekable'] && \substr($meta['uri'], 0, 9) !== 'php://std';
    }
}
