<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Task;

use Innmind\Mantle\{
    Task,
    Suspend,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Stream\{
    Readable,
    Writable,
};
use Innmind\Immutable\Set;

/**
 * @internal
 */
final class PendingActivity
{
    private Task $task;
    private Suspend\Action $action;

    /**
     * @psalm-mutation-free
     */
    private function __construct(Task $task, Suspend\Action $action)
    {
        $this->task = $task;
        $this->action = $action;
    }

    /**
     * @psalm-pure
     */
    public static function of(Task $task, Suspend\Action $action): self
    {
        return new self($task, $action);
    }

    /**
     * @param Set<Readable> $toRead
     * @param Set<Writable> $toWrite
     */
    public function continue(
        ElapsedPeriod $took,
        Set $toRead,
        Set $toWrite,
    ): self|Activated {
        return $this
            ->action
            ->continue($took, $toRead, $toWrite)
            ->match(
                fn($action) => new self($this->task, $action),
                fn($toSend) => Activated::of($this->task, $toSend),
            );
    }

    /**
     * @psalm-mutation-free
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
