<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Suspend;

use Innmind\TimeContinuum\PointInTime;

final class Synchronous implements Strategy
{
    private function __construct()
    {
    }

    public function __invoke(PointInTime $resumedAt): bool
    {
        return false;
    }

    public static function of(): self
    {
        return new self;
    }
}
