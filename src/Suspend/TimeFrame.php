<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Suspend;

use Innmind\TimeContinuum\{
    Clock,
    ElapsedPeriod,
    PointInTime,
};

final class TimeFrame implements Strategy
{
    private Clock $clock;
    private ElapsedPeriod $frame;

    private function __construct(Clock $clock, ElapsedPeriod $frame)
    {
        $this->clock = $clock;
        $this->frame = $frame;
    }

    public function __invoke(PointInTime $resumedAt): bool
    {
        return $this
            ->clock
            ->now()
            ->elapsedSince($resumedAt)
            ->longerThan($this->frame);
    }

    /**
     * @return callable(): Strategy
     */
    public static function of(Clock $clock, ElapsedPeriod $frame): callable
    {
        return static fn() => new self($clock, $frame);
    }
}
