<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Task;

use Innmind\Mantle\{
    Task,
    Suspend,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Stream\Watch\Ready;
use Innmind\Immutable\Maybe;

/**
 * @internal
 * @template T
 */
final class PendingActivity
{
    /** @var Task<T> */
    private Task $task;
    private Suspend\Action $action;

    /**
     * @psalm-mutation-free
     *
     * @param Task<T> $task
     */
    private function __construct(Task $task, Suspend\Action $action)
    {
        $this->task = $task;
        $this->action = $action;
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param Task<A> $task
     *
     * @return self<A>
     */
    public static function of(Task $task, Suspend\Action $action): self
    {
        return new self($task, $action);
    }

    /**
     * @param Maybe<Ready> $ready
     *
     * @return self<T>|Activated<T>
     */
    public function continue(
        ElapsedPeriod $took,
        Maybe $ready,
    ): self|Activated {
        return $this
            ->action
            ->continue($took, $ready)
            ->match(
                fn($action) => new self($this->task, $action),
                fn($toSend) => Activated::of($this->task, $toSend),
            );
    }

    /**
     * @psalm-mutation-free
     *
     * @return Task<T>
     */
    public function task(): Task
    {
        return $this->task;
    }

    /**
     * @psalm-mutation-free
     */
    public function action(): Suspend\Action
    {
        return $this->action;
    }
}
