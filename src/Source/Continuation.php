<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Source;

use Innmind\Mantle\Task;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @template C
 * @template R
 */
final class Continuation
{
    /** @var C */
    private mixed $carry;
    /** @var Sequence<Task<R>> */
    private Sequence $tasks;
    private bool $terminate;

    /**
     * @param C $carry
     * @param Sequence<Task<R>> $tasks
     */
    private function __construct(mixed $carry, Sequence $tasks, bool $terminate)
    {
        $this->carry = $carry;
        $this->tasks = $tasks;
        $this->terminate = $terminate;
    }

    /**
     * @internal
     * @psalm-pure
     * @template A
     *
     * @param A $carry
     *
     * @return self<A, mixed>
     */
    public static function of(mixed $carry): self
    {
        return new self($carry, Sequence::of(), false);
    }

    /**
     * @param C $carry
     *
     * @return self<C, R>
     */
    public function carryWith(mixed $carry): self
    {
        return new self($carry, $this->tasks, $this->terminate);
    }

    /**
     * @template A
     *
     * @param Sequence<Task<A>> $tasks
     *
     * @return self<C, R|A>
     */
    public function launch(Sequence $tasks): self
    {
        /** @psalm-suppress InvalidArgument */
        return new self(
            $this->carry,
            $this->tasks->append($tasks),
            $this->terminate,
        );
    }

    /**
     * @return self<C, R>
     */
    public function terminate(): self
    {
        return new self($this->carry, $this->tasks, true);
    }

    /**
     * @internal
     *
     * @return C
     */
    public function carry(): mixed
    {
        return $this->carry;
    }

    /**
     * @internal
     * @template R1
     * @template R2
     *
     * @param callable(C, Sequence<Task<R>>): R1 $resume
     * @param callable(C, Sequence<Task<R>>): R2 $terminate
     *
     * @return R1|R2
     */
    public function match(callable $resume, callable $terminate): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return match ($this->terminate) {
            true => $terminate($this->carry, $this->tasks),
            false => $resume($this->carry, $this->tasks),
        };
    }
}
