<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Source;

use Innmind\Mantle\Source;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * @internal
 * @template C
 * @template R
 */
final class Context
{
    /** @var Source<C, R> */
    private Source $source;
    /** @var Continuation<C, R> */
    private Continuation $continuation;
    /** @var Sequence<R> */
    private Sequence $results;

    /**
     * @param Source<C, R> $source
     * @param Continuation<C, R> $continuation
     * @param Sequence<R> $results
     */
    private function __construct(
        Source $source,
        Continuation $continuation,
        Sequence $results,
    ) {
        $this->source = $source;
        $this->continuation = $continuation;
        $this->results = $results;
    }

    /**
     * @return self<C, R>
     */
    public function __invoke(OperatingSystem $os): self
    {
        $continuation = ($this->source)(
            $this->continuation->carry(),
            $os,
            $this->continuation,
            $this->results,
        );

        return new self($this->source, $continuation, Sequence::of());
    }

    /**
     * @template A
     * @template B
     *
     * @param Source<A, B> $source
     * @param A $carry
     *
     * @return self<A, B>
     */
    public static function of(Source $source, mixed $carry): self
    {
        /** @var Continuation<A, B> */
        $continuation = Continuation::of($carry);

        return new self($source, $continuation, Sequence::of());
    }

    /**
     * @param Sequence<R> $results
     */
    public function withResults(Sequence $results): self
    {
        return new self($this->source, $this->continuation, $results);
    }

    /**
     * @return Continuation<C, R>
     */
    public function continuation(): Continuation
    {
        return $this->continuation;
    }
}
