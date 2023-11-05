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
final class BrandNew
{
    /** @var Task<T> */
    private Task $task;

    /**
     * @psalm-mutation-free
     *
     * @param Task<T> $task
     */
    private function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param Task<A> $task
     *
     * @return self<A>
     */
    public static function of(Task $task): self
    {
        return new self($task);
    }

    /**
     * @return PendingActivity<T>|Terminated<T>
     */
    public function continue(OperatingSystem $os): PendingActivity|Terminated
    {
        $returned = $this->task->start($os);

        if ($returned instanceof Suspend\Action) {
            return PendingActivity::of($this->task, $returned);
        }

        return Terminated::of($returned);
    }
}
