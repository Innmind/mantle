<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Suspend\{
    Strategy,
    Action,
};
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

    /**
     * @template T
     *
     * @param Action<T> $action
     *
     * @return T
     */
    public function __invoke(Action $action): mixed
    {
        /** @var T */
        return \Fiber::suspend($action);
    }

    public static function of(Clock $clock, Strategy $shouldSuspend): self
    {
        return new self($clock, $shouldSuspend);
    }
}
