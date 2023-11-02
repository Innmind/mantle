<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Asynchronous\Stream;

use Innmind\Mantle\Suspend;
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Stream\{
    Watch as WatchInterface,
    Watch\Ready,
    Stream,
    Readable,
    Writable,
};
use Innmind\Immutable\{
    Maybe,
    Set,
};

/**
 * @internal
 */
final class Watch implements WatchInterface
{
    private Suspend $suspend;
    /** @var Maybe<ElapsedPeriod> */
    private Maybe $timeout;
    /** @var Set<Readable> */
    private Set $forRead;
    /** @var Set<Writable> */
    private Set $forWrite;

    /**
     * @psalm-mutation-free
     *
     * @param Maybe<ElapsedPeriod> $timeout
     * @param Set<Readable> $forRead
     * @param Set<Writable> $forWrite
     */
    private function __construct(
        Suspend $suspend,
        Maybe $timeout,
        Set $forRead,
        Set $forWrite,
    ) {
        $this->suspend = $suspend;
        $this->timeout = $timeout;
        $this->forRead = $forRead;
        $this->forWrite = $forWrite;
    }

    public function __invoke(): Maybe
    {
        ($this->suspend)();

        /** @var Maybe<Ready> */
        return Maybe::nothing();
    }

    /**
     * @psalm-pure
     */
    public static function waitForever(Suspend $suspend): self
    {
        /** @var Maybe<ElapsedPeriod> */
        $timeout = Maybe::nothing();

        return new self(
            $suspend,
            $timeout,
            Set::of(),
            Set::of(),
        );
    }

    /**
     * @psalm-pure
     */
    public static function timeoutAfter(
        Suspend $suspend,
        ElapsedPeriod $timeout,
    ): self {
        return new self(
            $suspend,
            Maybe::just($timeout),
            Set::of(),
            Set::of(),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function forRead(Readable $read, Readable ...$reads): self
    {
        return new self(
            $this->suspend,
            $this->timeout,
            $this->forRead->merge(Set::of($read, ...$reads)),
            $this->forWrite,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function forWrite(Writable $write, Writable ...$writes): self
    {
        return new self(
            $this->suspend,
            $this->timeout,
            $this->forRead,
            $this->forWrite->merge(Set::of($write, ...$writes)),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function unwatch(Stream $stream): self
    {
        return new self(
            $this->suspend,
            $this->timeout,
            $this->forRead->filter(static fn($known) => $stream !== $known),
            $this->forWrite->filter(static fn($known) => $stream !== $known),
        );
    }
}
