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
final class BrandNew
{
    private Task $task;

    /**
     * @psalm-mutation-free
     */
    private function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * @psalm-pure
     */
    public static function of(Task $task): self
    {
        return new self($task);
    }

    public function continue(OperatingSystem $os): PendingActivity|Terminated
    {
        /** @var mixed */
        $returned = $this->task->continue($os);

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
