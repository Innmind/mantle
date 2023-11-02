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
 */
final class Activated
{
    private Task $task;
    private mixed $toSend;

    /**
     * @psalm-mutation-free
     */
    private function __construct(Task $task, mixed $toSend)
    {
        $this->task = $task;
        $this->toSend = $toSend;
    }

    /**
     * @psalm-pure
     */
    public static function of(Task $task, mixed $toSend): self
    {
        return new self($task, $toSend);
    }

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
     */
    public function task(): Task
    {
        return $this->task;
    }
}
