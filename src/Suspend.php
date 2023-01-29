<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Suspend\Strategy;
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
};

final class Suspend
{
    private Clock $clock;
    private Strategy $shouldSuspend;
    private PointInTime $resumedAt;

    private function __construct(Clock $clock, Strategy $shouldSuspend)
    {
        $this->clock = $clock;
        $this->shouldSuspend = $shouldSuspend;
        $this->resumedAt = $clock->now();
    }

    public function __invoke(): void
    {
        if (($this->shouldSuspend)($this->resumedAt)) {
            \Fiber::suspend();
            $this->resumedAt = $this->clock->now();
        }
    }

    public static function of(Clock $clock, Strategy $shouldSuspend): self
    {
        return new self($clock, $shouldSuspend);
    }
}
