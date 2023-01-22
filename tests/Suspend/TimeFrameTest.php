<?php
declare(strict_types = 1);

namespace Tests\Innmind\Mantle\Suspend;

use Innmind\Mantle\{
    Suspend\TimeFrame,
    Suspend\Strategy,
};
use Innmind\TimeContinuum\{
    Earth\Clock,
    Earth\ElapsedPeriod,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class TimeFrameTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Strategy::class,
            TimeFrame::of(new Clock, ElapsedPeriod::of(1))(),
        );
    }

    public function testShouldSuspend()
    {
        $this
            ->forAll(Set\Integers::between(1, 450000))
            ->then(function($microseconds) {
                $shouldSuspend = TimeFrame::of(new Clock, ElapsedPeriod::of(500))();

                $this->assertFalse($shouldSuspend());
                \usleep($microseconds);
                $this->assertFalse($shouldSuspend());
            });

        $shouldSuspend = TimeFrame::of(new Clock, ElapsedPeriod::of(500))();

        \usleep(501000);
        $this->assertTrue($shouldSuspend());
    }
}
