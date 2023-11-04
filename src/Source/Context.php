<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Source;

use Innmind\Mantle\Source;
use Innmind\OperatingSystem\OperatingSystem;

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

    /**
     * @param Source<C> $source
     * @param Continuation<C> $continuation
     */
    private function __construct(Source $source, Continuation $continuation)
    {
        $this->source = $source;
        $this->continuation = $continuation;
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
        );

        return new self($this->source, $continuation);
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
        return new self($source, Continuation::of($carry));
    }

    /**
     * @return Continuation<C>
     */
    public function continuation(): Continuation
    {
        return $this->continuation;
    }
}
