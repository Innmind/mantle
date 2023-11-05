<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Asynchronous\Stream\Capabilities;

use Innmind\Mantle\{
    Suspend,
    Asynchronous,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Stream;

/**
 * @internal
 */
final class Watch implements Stream\Capabilities\Watch
{
    private Suspend $suspend;

    private function __construct(Suspend $suspend)
    {
        $this->suspend = $suspend;
    }

    public static function of(Suspend $suspend): self
    {
        return new self($suspend);
    }

    public function waitForever(): Stream\Watch
    {
        return Asynchronous\Stream\Watch::waitForever($this->suspend);
    }

    public function timeoutAfter(ElapsedPeriod $timeout): Stream\Watch
    {
        return Asynchronous\Stream\Watch::timeoutAfter($this->suspend, $timeout);
    }
}
