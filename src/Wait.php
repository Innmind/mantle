<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Source\Context;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    Earth,
    Earth\Period\Millisecond,
};
use Innmind\Stream\{
    Stream,
    Readable,
    Writable,
    Watch\Ready,
};
use Innmind\Immutable\{
    Sequence,
    Set,
    Maybe,
    Predicate\Instance,
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
    /** @var Maybe<ElapsedPeriod> */
    private Maybe $timeout;
    /** @var Set<Readable> */
    private Set $forRead;
    /** @var Set<Writable> */
    private Set $forWrite;
    /** @var Set<Readable> */
    private Set $files;
    private bool $poll;

    /**
     * @param Maybe<Task\PendingActivity<Context<C, R>>> $source
     * @param Sequence<Task\PendingActivity<R>> $tasks
     * @param Maybe<ElapsedPeriod> $timeout
     * @param Set<Readable> $forRead
     * @param Set<Writable> $forWrite
     * @param Set<Readable> $files
     */
    private function __construct(
        OperatingSystem $os,
        Maybe $source,
        Sequence $tasks,
        Maybe $timeout,
        Set $forRead,
        Set $forWrite,
        Set $files,
        bool $poll,
    ) {
        $this->os = $os;
        $this->source = $source;
        $this->tasks = $tasks;
        $this->timeout = $timeout;
        $this->forRead = $forRead;
        $this->forWrite = $forWrite;
        $this->files = $files;
        $this->poll = $poll;
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
        $shortestTimeout = match ($this->poll) {
            true => Maybe::just(Earth\ElapsedPeriod::of(0)),
            false => $this->timeout,
        };

        if ($this->forRead->empty() && $this->forWrite->empty()) {
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
        $watch = $this->os->sockets()->watch($timeout);
        $watch = $this->forRead->match(
            static fn($read, $rest) => $watch->forRead($read, ...$rest->toList()),
            static fn() => $watch,
        );
        $watch = $this->forWrite->match(
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
        $ready = $watch()->map(fn($ready) => new Ready(
            $ready->toRead()->merge($this->files),
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
        /** @var Maybe<ElapsedPeriod> */
        $timeout = Maybe::nothing();

        return new self(
            $os,
            $source,
            Sequence::of(),
            $timeout,
            Set::of(),
            Set::of(),
            Set::of(),
            false,
        );
    }

    /**
     * @template A
     * @template B
     *
     * @param Maybe<Context<A, B>|Task\PendingActivity<Context<A, B>>> $source
     *
     * @return self<A, B>
     */
    public function withSource(Maybe $source): self
    {
        $sourceTask = $source->keep(Instance::of(Task\PendingActivity::class));
        $forRead = $sourceTask->match(
            static fn($task) => $task->action()->forRead(),
            static fn() => Set::of(),
        );

        /**
         * @psalm-suppress MixedArgumentTypeCoercion Force erase the type on purpose
         * @var self<A, B>
         */
        return new self(
            $this->os,
            $sourceTask,
            $this->tasks,
            $sourceTask->flatMap(static fn($source) => $source->action()->timeout()),
            $forRead,
            $sourceTask->match(
                static fn($task) => $task->action()->forWrite(),
                static fn() => Set::of(),
            ),
            $forRead->filter(self::isFile(...)),
            $source
                ->keep(Instance::of(Context::class))
                ->match(
                    static fn() => true, // poll the tasks to allow restarting the source as soon as possible
                    static fn() => false,
                ),
        );
    }

    /**
     * @param Task\PendingActivity<R> $task
     *
     * @return self<C, R>
     */
    public function with(Task\PendingActivity $task): self
    {
        $timeout = $task->action()->timeout();

        return new self(
            $this->os,
            $this->source,
            $this->tasks->add($task),
            $this
                ->timeout
                ->flatMap(static fn($shortest) => $timeout->map(
                    static fn($new) => match ($shortest->longerThan($new)) {
                        true => $new,
                        false => $shortest,
                    },
                ))
                ->otherwise(static fn() => $timeout),
            $this->forRead->merge($task->action()->forRead()),
            $this->forWrite->merge($task->action()->forWrite()),
            $this->files->merge(
                $task->action()->forRead()->filter(self::isFile(...)),
            ),
            $this->poll,
        );
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
