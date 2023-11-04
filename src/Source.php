<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Source\Continuation;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * The sole goal of this class is to abstract away the whole callable type.
 *
 * @internal
 * @template C
 */
final class Source
{
    /** @var callable(C, OperatingSystem, Continuation<C>, Sequence<mixed>): Continuation<C> */
    private $source;

    /**
     * @param callable(C, OperatingSystem, Continuation<C>, Sequence<mixed>): Continuation<C> $source
     */
    private function __construct(callable $source)
    {
        $this->source = $source;
    }

    /**
     * @param C $carry
     * @param Continuation<C> $continuation
     * @param Sequence<mixed> $results
     *
     * @return Continuation<C>
     */
    public function __invoke(
        mixed $carry,
        OperatingSystem $os,
        Continuation $continuation,
        Sequence $results,
    ): Continuation {
        return ($this->source)($carry, $os, $continuation, $results);
    }

    /**
     * @template A
     *
     * @param callable(A, OperatingSystem, Continuation<A>, Sequence<mixed>): Continuation<A> $source
     *
     * @return self<A>
     */
    public static function of(callable $source): self
    {
        return new self($source);
    }
}
