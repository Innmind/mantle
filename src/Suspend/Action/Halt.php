<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Suspend\Action;

use Innmind\Mantle\Suspend\Action;
use Innmind\TimeContinuum\Period;

/**
 * @internal
 * @psalm-immutable
 * @implements Action<void>
 */
final class Halt implements Action
{
    private Period $period;

    private function __construct(Period $period)
    {
        $this->period = $period;
    }

    /**
     * @psalm-pure
     */
    public static function of(Period $period): self
    {
        return new self($period);
    }
}
