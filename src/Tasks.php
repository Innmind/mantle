<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Tasks
{
    /** @var Sequence<Task\BrandNew|Task\PendingActivity|Task\Activated|Task\Terminated> */
    private Sequence $all;

    /**
     * @psalm-mutation-free
     *
     * @param Sequence<Task\BrandNew|Task\PendingActivity|Task\Activated|Task\Terminated> $all
     */
    private function __construct(Sequence $all)
    {
        $this->all = $all;
    }

    public static function none(): self
    {
        return new self(Sequence::of());
    }

    /**
     * @psalm-mutation-free
     *
     * @param Sequence<Task> $new
     */
    public function append(Sequence $new): self
    {
        return new self(
            $this
                ->all
                ->append($new->map(Task\BrandNew::of(...)))
                ->filter(static fn($task) => !($task instanceof Task\Terminated)), // cleanup
        );
    }

    public function continue(OperatingSystem $os): self
    {
        $partition = $this->all->partition(
            static fn($task) => $task instanceof Task\BrandNew ||
                $task instanceof Task\Activated,
        );

        /** @var Sequence<Task\BrandNew|Task\Activated> */
        $resumable = $partition
            ->get(true)
            ->toSequence()
            ->flatMap(static fn($tasks) => $tasks);
        /** @var Sequence<Task\PendingActivity|Task\Terminated> */
        $nonActionable = $partition
            ->get(false)
            ->toSequence()
            ->flatMap(static fn($tasks) => $tasks);

        return new self(
            $resumable
                ->map(static fn($task) => $task->continue($os))
                ->append($nonActionable),
        );
    }

    public function wait(Wait $wait): self
    {
        $partition = $this->all->partition(
            static fn($task) => $task instanceof Task\PendingActivity,
        );
        /** @var Sequence<Task\PendingActivity> */
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
        $tasks = $wait();

        /** @psalm-suppress InvalidArgument */
        return new self($tasks->append($rest));
    }

    /**
     * @return Sequence<Task>
     */
    public function active(): Sequence
    {
        /**
         * @todo build an Or predicate in innmind/immutable
         * @psalm-suppress MixedReturnTypeCoercion
         * @psalm-suppress MissingClosureReturnType
         * @psalm-suppress PossiblyUndefinedMethod
         * @var Sequence<Task>
         */
        return $this
            ->all
            ->filter(static fn($task) => !($task instanceof Task\Terminated))
            ->map(static fn($wrapped) => $wrapped->task());
    }
}
