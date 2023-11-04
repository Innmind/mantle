<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Task;

use Innmind\Mantle\Task;

/**
 * @internal
 * @psalm-immutable
 * @template T
 */
final class Terminated
{
    /** @var Task<T> */
    private Task $task;
    /** @var T */
    private mixed $returned;

    /**
     * @param Task<T> $task
     * @param T $returned
     */
    private function __construct(Task $task, mixed $returned)
    {
        $this->task = $task;
        $this->returned = $returned;
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param Task<A> $task
     * @param A $returned
     *
     * @return self<A>
     */
    public static function of(Task $task, mixed $returned): self
    {
        return new self($task, $returned);
    }

    /**
     * @return T
     */
    public function returned(): mixed
    {
        return $this->returned;
    }
}
