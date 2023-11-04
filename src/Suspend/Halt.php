<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Suspend;

use Innmind\TimeContinuum\{
    Period,
    ElapsedPeriod,
    Earth,
};
use Innmind\TimeWarp\PeriodToMilliseconds;
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
 * @implements Action<void>
 */
final class Halt implements Action
{
    private ElapsedPeriod $period;

    private function __construct(ElapsedPeriod $period)
    {
        $this->period = $period;
    }

    /**
     * @psalm-pure
     */
    public static function of(Period $period): self
    {
        /**
         * @psalm-suppress ImpureMethodCall todo fix this in innmind/time-warp
         * @var 0|positive-int
         */
        $milliseconds = (new PeriodToMilliseconds)($period);

        return new self(Earth\ElapsedPeriod::of($milliseconds));
    }

    public function timeout(): Maybe
    {
        return Maybe::just($this->period);
    }

    /**
     * @return Set<Readable>
     */
    public function forRead(): Set
    {
        return Set::of();
    }

    /**
     * @return Set<Writable>
     */
    public function forWrite(): Set
    {
        return Set::of();
    }

    public function continue(
        ElapsedPeriod $took,
        Maybe $ready,
    ): Either {
        if ($this->period->longerThan($took)) {
            /** @var positive-int */
            $remaining = $this->period->milliseconds() - $took->milliseconds();

            /** @var Either<void, Action<void>> */
            return Either::right(new self(Earth\ElapsedPeriod::of($remaining)));
        }

        /** @var Either<void, Action<void>> */
        return Either::left(null);
    }
}
