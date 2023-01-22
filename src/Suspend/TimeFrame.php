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
    private PointInTime $lastSuspended;

    private function __construct(Clock $clock, ElapsedPeriod $frame)
    {
        $this->clock = $clock;
        $this->frame = $frame;
        $this->lastSuspended = $clock->now();
    }

    public function __invoke(): bool
    {
        $shouldSuspend = $this
            ->clock
            ->now()
            ->elapsedSince($this->lastSuspended)
            ->longerThan($this->frame);

        if ($shouldSuspend) {
            $this->lastSuspended = $this->clock->now();
        }

        return $shouldSuspend;
    }

    /**
     * @return callable(): Strategy
     */
    public static function of(Clock $clock, ElapsedPeriod $frame): callable
    {
        return static fn() => new self($clock, $frame);
    }
}
