<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Suspend;

use Innmind\TimeContinuum\PointInTime;

interface Strategy
{
    /**
     * Return true when you want the task to be suspended
     */
    public function __invoke(PointInTime $resumedAt): bool;
}
