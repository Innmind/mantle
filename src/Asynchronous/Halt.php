<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Asynchronous;

use Innmind\Mantle\Suspend;
use Innmind\TimeWarp\Halt as HaltInterface;
use Innmind\TimeContinuum\Period;

/**
 * @internal
 */
final class Halt implements HaltInterface
{
    private Suspend $suspend;

    private function __construct(Suspend $suspend)
    {
        $this->suspend = $suspend;
    }

    public function __invoke(Period $timeout): void
    {
        ($this->suspend)(Suspend\Action\Halt::of($timeout));
    }

    public static function of(Suspend $suspend): self
    {
        return new self($suspend);
    }
}
