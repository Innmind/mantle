<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Suspend;

use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Stream\{
    Readable,
    Writable,
};
use Innmind\Immutable\{
    Maybe,
    Set,
    Either,
};

/**
 * @internal
 * @psalm-immutable
 * @template T
 */
interface Action
{
    /**
     * @return Maybe<ElapsedPeriod>
     */
    public function timeout(): Maybe;

    /**
     * @return Set<Readable>
     */
    public function forRead(): Set;

    /**
     * @return Set<Writable>
     */
    public function forWrite(): Set;

    /**
     * @param Set<Readable> $toRead
     * @param Set<Writable> $toWrite
     *
     * @return Either<T, self<T>>
     */
    public function continue(
        ElapsedPeriod $took,
        Set $toRead,
        Set $toWrite,
    ): Either;
}
