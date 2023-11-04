<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Suspend;

use Innmind\TimeContinuum\{
    ElapsedPeriod,
    Earth,
};
use Innmind\Stream\{
    Readable,
    Writable,
    Watch\Ready,
};
use Innmind\Immutable\{
    Maybe,
    Set,
    Either,
};

/**
 * @internal
 * @psalm-immutable
 * @implements Action<Maybe<Ready>>
 */
final class Watch implements Action
{
    /** @var Maybe<ElapsedPeriod> */
    private Maybe $timeout;
    /** @var Set<Readable> */
    private Set $forRead;
    /** @var Set<Writable> */
    private Set $forWrite;

    /**
     * @param Maybe<ElapsedPeriod> $timeout
     * @param Set<Readable> $forRead
     * @param Set<Writable> $forWrite
     */
    private function __construct(
        Maybe $timeout,
        Set $forRead,
        Set $forWrite,
    ) {
        $this->timeout = $timeout;
        $this->forRead = $forRead;
        $this->forWrite = $forWrite;
    }

    /**
     * @param Maybe<ElapsedPeriod> $timeout
     * @param Set<Readable> $forRead
     * @param Set<Writable> $forWrite
     */
    public static function of(
        Maybe $timeout,
        Set $forRead,
        Set $forWrite,
    ): self {
        return new self($timeout, $forRead, $forWrite);
    }

    public function timeout(): Maybe
    {
        return $this->timeout;
    }

    /**
     * @return Set<Readable>
     */
    public function forRead(): Set
    {
        return $this->forRead;
    }

    /**
     * @return Set<Writable>
     */
    public function forWrite(): Set
    {
        return $this->forWrite;
    }

    public function continue(
        ElapsedPeriod $took,
        Maybe $ready,
    ): Either {
        $timedOut = $this
            ->timeout
            ->filter(
                static fn($timeout) => $took->longerThan($timeout) ||
                    $took->equals($timeout),
            )
            ->match(
                static fn() => true,
                static fn() => false,
            );

        if ($timedOut) {
            /** @var Either<Maybe<Ready>, Action<Maybe<Ready>>> */
            return Either::left(Maybe::just(new Ready(
                Set::of(),
                Set::of(),
            )));
        }

        /** @var Either<Maybe<Ready>, Action<Maybe<Ready>>> */
        return $ready->match(
            fn($ready) => $this->continueOnReady($took, $ready),
            static fn() => Either::left(Maybe::nothing()), // notify each fiber that we failed to watch the streams
        );
    }

    /**
     * @return Either<Maybe<Ready>, Action<Maybe<Ready>>>
     */
    private function continueOnReady(ElapsedPeriod $took, Ready $ready): Either
    {
        $ownToRead = $ready->toRead()->intersect($this->forRead);
        $ownToWrite = $ready->toWrite()->intersect($this->forWrite);

        if ($ownToRead->empty() && $ownToWrite->empty()) {
            /** @var Either<Maybe<Ready>, Action<Maybe<Ready>>> */
            return Either::right(new self(
                $this->timeout->map(static function($timeout) use ($took) {
                    /** @var positive-int */
                    $remaining = $timeout->milliseconds() - $took->milliseconds();

                    return Earth\ElapsedPeriod::of($remaining);
                }),
                $this->forRead,
                $this->forWrite,
            ));
        }

        /** @var Either<Maybe<Ready>, Action<Maybe<Ready>>> */
        return Either::left(Maybe::just(new Ready(
            $ownToRead,
            $ownToWrite,
        )));
    }
}
