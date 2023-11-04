<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Source\Context;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Sequence,
    Either,
    Maybe,
    Predicate\Instance,
};

/**
 * @internal
 * @template C
 * @template R
 */
final class Tasks
{
    /** @var Either<C, Context<C, R>|Task\PendingActivity<Context<C, R>>|Task\Activated<Context<C, R>>|Task\Terminated<Context<C, R>>> */
    private Either $source;
    /** @var Sequence<Task\BrandNew<R>|Task\PendingActivity<R>|Task\Activated<R>|Task\Terminated<R>> */
    private Sequence $all;

    /**
     * @psalm-mutation-free
     *
     * @param Either<C, Context<C, R>|Task\PendingActivity<Context<C, R>>|Task\Activated<Context<C, R>>|Task\Terminated<Context<C, R>>> $source
     * @param Sequence<Task\BrandNew<R>|Task\PendingActivity<R>|Task\Activated<R>|Task\Terminated<R>> $all
     */
    private function __construct(Either $source, Sequence $all)
    {
        $this->source = $source;
        $this->all = $all;
    }

    /**
     * @template A
     * @template B
     *
     * @param Context<A, B> $source
     *
     * @return self<A, B>
     */
    public static function of(Context $source): self
    {
        return new self(
            Either::right($source),
            Sequence::of(),
        );
    }

    /**
     * @return self<C, R>
     */
    public function continue(OperatingSystem $os): self
    {
        $partition = $this->all->partition(Instance::of(Task\Terminated::class));
        /** @var Sequence<R> */
        $terminated = $partition
            ->get(true)
            ->toSequence()
            ->flatMap(static fn($tasks) => $tasks)
            ->keep(Instance::of(Task\Terminated::class)) // to please psalm
            ->map(static fn($task): mixed => $task->returned());
        /** @var Sequence<Task\BrandNew<R>|Task\PendingActivity<R>|Task\Activated<R>> */
        $active = $partition
            ->get(false)
            ->toSequence()
            ->flatMap(static fn($tasks) => $tasks);
        // Start the source fiber if it's brand new or resume it if it has been
        // activated in a previous iteration
        $source = $this
            ->source
            ->flatMap(static fn($task) => match (true) {
                $task instanceof Context => Either::right(Task\BrandNew::of(Task::of(
                    $task->withResults($terminated),
                ))),
                $task instanceof Task\Activated => Either::right($task),
                default => Either::left($task)
            })
            ->map(static fn($task) => $task->continue($os))
            ->otherwise(static fn($left) => match (true) {
                $left instanceof Task\PendingActivity => Either::right($left),
                $left instanceof Task\Terminated => Either::right($left),
                default => Either::left($left),
            });
        // Add any new tasks returned by the source to the list of tasks to run
        $newTasks = $source
            ->maybe()
            ->keep(Instance::of(Task\Terminated::class))
            ->map(static fn($task): mixed => $task->returned())
            ->keep(Instance::of(Context::class))
            ->toSequence()
            ->flatMap(static fn($context) => $context->continuation()->match(
                static fn($_, $tasks) => $tasks,
                static fn($_, $tasks) => $tasks,
            ))
            ->map(Task\BrandNew::of(...));
        // If the source fiber has terminated and the continuation tells that
        // the source has terminated producing tasks then we move the carried
        // value to the left in order to wait all tasks to finish.
        // If the source fiber has terminated but the continuation tells that
        // the source can be resumed then we the source in a brand new task.
        // If none of the above then we leave it as is, meaning pending or activated
        /**
         * @psalm-suppress MixedArgument Psalm lose the type of the returned value
         * @var Either<C, Context<C, R>|Task\PendingActivity<Context<C, R>>|Task\Activated<Context<C, R>>|Task\Terminated<Context<C, R>>>
         */
        $source = $source->flatMap(static fn($task) => match (true) {
            $task instanceof Task\Terminated && $task->returned() instanceof Context => $task
                ->returned()
                ->continuation()
                ->match(
                    static fn() => Either::right($task->returned()),
                    static fn($carry) => Either::left($carry),
                ),
            default => Either::right($task),
        });

        $partition = $active->partition(
            static fn($task) => $task instanceof Task\BrandNew ||
                $task instanceof Task\Activated,
        );

        /** @var Sequence<Task\BrandNew<R>|Task\Activated<R>> */
        $resumable = $partition
            ->get(true)
            ->toSequence()
            ->flatMap(static fn($tasks) => $tasks);
        /** @var Sequence<Task\PendingActivity<R>> */
        $nonActionable = $partition
            ->get(false)
            ->toSequence()
            ->flatMap(static fn($tasks) => $tasks);

        /** @psalm-suppress InvalidArgument */
        return new self(
            $source,
            $newTasks
                ->append($resumable)
                ->map(static fn($task) => $task->continue($os))
                ->append($nonActionable),
        );
    }

    /**
     * @return self<C, R>
     */
    public function wait(Wait $wait): self
    {
        /** @var Maybe<Task\PendingActivity<Context<C, R>>> */
        $source = $this
            ->source
            ->maybe()
            ->keep(Instance::of(Task\PendingActivity::class));
        $wait = $wait->withSource($source);
        $partition = $this->all->partition(
            static fn($task) => $task instanceof Task\PendingActivity,
        );
        /** @var Sequence<Task\PendingActivity<R>> */
        $pending = $partition
            ->get(true)
            ->toSequence()
            ->flatMap(static fn($tasks) => $tasks);
        $rest = $partition
            ->get(false)
            ->toSequence()
            ->flatMap(static fn($tasks) => $tasks);

        $wait = $pending->reduce(
            $wait,
            static fn(Wait $wait, $task) => $wait->with($task),
        );
        [$source, $tasks] = $wait();

        /** @psalm-suppress InvalidArgument */
        return new self(
            $source->match(
                static fn($task) => Either::right($task),
                fn() => $this->source,
            ),
            $tasks->append($rest),
        );
    }

    /**
     * @return C
     */
    public function carry(): mixed
    {
        /** @var C */
        return $this->source->match(
            static fn() => throw new \LogicException('Source still active'),
            static fn(mixed $carry): mixed => $carry,
        );
    }

    public function active(): bool
    {
        $sourceActive = $this->source->match(
            static fn() => true,
            static fn() => false,
        );

        if ($sourceActive) {
            return true;
        }

        /**
         * @todo build an Or predicate in innmind/immutable
         */
        return !$this
            ->all
            ->filter(static fn($task) => !($task instanceof Task\Terminated))
            ->empty();
    }
}
