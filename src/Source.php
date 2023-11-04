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
 * @template R
 */
final class Source
{
    /** @var callable(C, OperatingSystem, Continuation<C, R>, Sequence<R>): Continuation<C, R> */
    private $source;

    /**
     * @param callable(C, OperatingSystem, Continuation<C, R>, Sequence<R>): Continuation<C, R> $source
     */
    private function __construct(callable $source)
    {
        $this->source = $source;
    }

    /**
     * @param C $carry
     * @param Continuation<C, R> $continuation
     * @param Sequence<R> $results
     *
     * @return Continuation<C, R>
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
     * @template B
     *
     * @param callable(A, OperatingSystem, Continuation<A, B>, Sequence<B>): Continuation<A, B> $source
     *
     * @return self<A, B>
     */
    public static function of(callable $source): self
    {
        return new self($source);
    }
}
