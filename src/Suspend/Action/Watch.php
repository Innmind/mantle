<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Suspend\Action;

use Innmind\Mantle\Suspend\Action;
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Stream\{
    Readable,
    Writable,
    Watch\Ready,
};
use Innmind\Immutable\{
    Maybe,
    Set,
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
}
