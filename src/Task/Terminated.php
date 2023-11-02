<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Task;

use Innmind\Mantle\Task;

/**
 * @internal
 */
final class Terminated
{
    private Task $task;
    private mixed $returned;

    /**
     * @psalm-mutation-free
     */
    private function __construct(Task $task, mixed $returned)
    {
        $this->task = $task;
        $this->returned = $returned;
    }

    /**
     * @psalm-pure
     */
    public static function of(Task $task, mixed $returned): self
    {
        return new self($task, $returned);
    }
}
