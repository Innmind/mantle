<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Source;

use Innmind\Mantle\Source;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * @internal
 * @template C
 */
final class Context
{
    /** @var Source<C> */
    private Source $source;
    /** @var Continuation<C> */
    private Continuation $continuation;
    /** @var Sequence<mixed> */
    private Sequence $results;

    /**
     * @param Source<C> $source
     * @param Continuation<C> $continuation
     * @param Sequence<mixed> $results
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
     * @return self<C>
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
     *
     * @param Source<A> $source
     * @param A $carry
     *
     * @return self<A>
     */
    public static function of(Source $source, mixed $carry): self
    {
        return new self($source, Continuation::of($carry), Sequence::of());
    }

    /**
     * @param Sequence<mixed> $results
     */
    public function withResults(Sequence $results): self
    {
        return new self($this->source, $this->continuation, $results);
    }

    /**
     * @return Continuation<C>
     */
    public function continuation(): Continuation
    {
        return $this->continuation;
    }
}
