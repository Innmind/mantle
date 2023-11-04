<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Task;

use Innmind\Mantle\{
    Task,
    Suspend,
};
use Innmind\OperatingSystem\OperatingSystem;

/**
 * @internal
 * @template T
 */
final class Activated
{
    /** @var Task<T> */
    private Task $task;
    private mixed $toSend;

    /**
     * @psalm-mutation-free
     *
     * @param Task<T> $task
     */
    private function __construct(Task $task, mixed $toSend)
    {
        $this->task = $task;
        $this->toSend = $toSend;
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param Task<A> $task
     *
     * @return self<A>
     */
    public static function of(Task $task, mixed $toSend): self
    {
        return new self($task, $toSend);
    }

    /**
     * @return PendingActivity<T>|Terminated<T>
     */
    public function continue(OperatingSystem $os): PendingActivity|Terminated
    {
        /** @var mixed */
        $returned = $this->task->resume($this->toSend);

        if ($returned instanceof Suspend\Action) {
            return PendingActivity::of($this->task, $returned);
        }

        return Terminated::of($this->task, $returned);
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
}
