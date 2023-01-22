<?php
declare(strict_types = 1);

namespace Tests\Innmind\Mantle\Suspend;

use Innmind\Mantle\{
    Suspend\TimeFrame,
    Suspend\Strategy,
};
use Innmind\TimeContinuum\{
    Clock,
    Earth\Period\Millisecond,
    Earth\ElapsedPeriod,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

class TimeFrameTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Strategy::class,
            TimeFrame::of($this->createMock(Clock::class), ElapsedPeriod::of(1))(),
        );
    }

    public function testShouldSuspend()
    {
        $this
            ->forAll(
                PointInTime::any(),
                Set\Integers::between(1, 500),
            )
            ->then(function($now, $allowed) {
                $clock = $this->createMock(Clock::class);
                $clock
                    ->expects($this->exactly(2))
                    ->method('now')
                    ->willReturnOnConsecutiveCalls(
                        $now,
                        $now->goForward(new Millisecond($allowed)),
                    );
                $shouldSuspend = TimeFrame::of($clock, ElapsedPeriod::of(500))();

                $this->assertFalse($shouldSuspend());
            });
        $this
            ->forAll(
                PointInTime::any(),
                Set\Integers::between(501, 1_000_000),
            )
            ->then(function($now, $trigger) {
                $clock = $this->createMock(Clock::class);
                $clock
                    ->expects($this->atLeast(2))
                    ->method('now')
                    ->willReturnOnConsecutiveCalls(
                        $now,
                        $now->goForward(new Millisecond($trigger)),
                        $now->goForward(new Millisecond($trigger)),
                    );
                $shouldSuspend = TimeFrame::of($clock, ElapsedPeriod::of(500))();

                $this->assertTrue($shouldSuspend());
            });
    }
}
