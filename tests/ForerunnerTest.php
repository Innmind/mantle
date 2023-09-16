<?php
declare(strict_types = 1);

namespace Tests\Innmind\Mantle;

use Innmind\Mantle\{
    Forerunner,
    Source,
    Suspend\Asynchronous,
    Suspend\Synchronous,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\Immutable\{
    Sequence,
    SideEffect,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class ForerunnerTest extends TestCase
{
    use BlackBox;

    /**
     * @dataProvider strategies
     */
    public function testSimpleRunner($strategy, $expected)
    {
        $queue = new \SplQueue;
        $source = Source\Predetermined::of(
            static fn($suspend) => Sequence::lazy(static function() use ($suspend) {
                yield 0;
                $suspend();
                yield 2;
                $suspend();
                yield 4;
                $suspend();
                yield 6;
                $suspend();
            })
                ->foreach(static fn($i) => $queue->enqueue($i)),
            static fn($suspend) => Sequence::lazy(static function() use ($suspend) {
                yield 1;
                $suspend();
                yield 3;
                $suspend();
                yield 5;
                $suspend();
                yield 7;
                $suspend();
                yield 9;
                $suspend();
                yield 11;
                $suspend();
            })
                ->foreach(static fn($i) => $queue->enqueue($i)),
        );

        $forerunner = Forerunner::of(new Clock, $strategy);
        $carry = new SideEffect;

        $this->assertSame($carry, $forerunner($carry, $source));

        $this->assertSame(
            $expected,
            \iterator_to_array($queue),
        );
    }

    public function testThrottleToOneTaskIsEquivalentToASynchronousStrategy()
    {
        $queue = new \SplQueue;
        $source = Source\Predetermined::of(
            static fn($suspend) => Sequence::lazy(static function() use ($suspend) {
                yield 0;
                $suspend();
                yield 2;
                $suspend();
                yield 4;
                $suspend();
                yield 6;
                $suspend();
            })
                ->foreach(static fn($i) => $queue->enqueue($i)),
            static fn($suspend) => Sequence::lazy(static function() use ($suspend) {
                yield 1;
                $suspend();
                yield 3;
                $suspend();
                yield 5;
                $suspend();
                yield 7;
                $suspend();
                yield 9;
                $suspend();
                yield 11;
                $suspend();
            })
                ->foreach(static fn($i) => $queue->enqueue($i)),
        );

        $forerunner = Forerunner::of(new Clock);
        $carry = new SideEffect;

        $this->assertSame(
            $carry,
            $forerunner($carry, Source\Throttle::of($source, 1)),
        );

        $this->assertSame(
            [0, 2, 4, 6, 1, 3, 5, 7, 9, 11],
            \iterator_to_array($queue),
        );
    }

    public function testThrottle()
    {
        $queue = new \SplQueue;
        $source = Source\Predetermined::of(
            static fn($suspend) => Sequence::lazy(static function() use ($suspend) {
                yield 0;
                $suspend();
                yield 2;
                $suspend();
                yield 4;
                $suspend();
                yield 6;
                $suspend();
            })
                ->foreach(static fn($i) => $queue->enqueue($i)),
            static fn($suspend) => Sequence::lazy(static function() use ($suspend) {
                yield 1;
                $suspend();
                yield 3;
                $suspend();
                yield 5;
                $suspend();
                yield 7;
                $suspend();
                yield 9;
                $suspend();
                yield 11;
                $suspend();
            })
                ->foreach(static fn($i) => $queue->enqueue($i)),
            static fn($suspend) => Sequence::lazy(static function() use ($suspend) {
                yield 8;
                $suspend();
                yield 10;
                $suspend();
                yield 12;
                $suspend();
                yield 14;
                $suspend();
                yield 16;
                $suspend();
                yield 18;
                $suspend();
            })
                ->foreach(static fn($i) => $queue->enqueue($i)),
            static fn($suspend) => Sequence::lazy(static function() use ($suspend) {
                yield 13;
                $suspend();
                yield 15;
                $suspend();
                yield 17;
                $suspend();
                yield 19;
                $suspend();
            })
                ->foreach(static fn($i) => $queue->enqueue($i)),
        );

        $forerunner = Forerunner::of(new Clock);
        $carry = new SideEffect;

        $this->assertSame(
            $carry,
            $forerunner($carry, Source\Throttle::of($source, 2)),
        );

        $this->assertSame(
            [0, 1, 2, 3, 4, 5, 6, 7, 9, 11, 8, 10, 12, 13, 14, 15, 16, 17, 18, 19],
            \iterator_to_array($queue),
        );
    }

    public static function strategies(): iterable
    {
        yield [
            null,
            [0, 1, 2, 3, 4, 5, 6, 7, 9, 11],
        ];
        yield [
            Asynchronous::of(...),
            [0, 1, 2, 3, 4, 5, 6, 7, 9, 11],
        ];
        yield [
            Synchronous::of(...),
            [0, 2, 4, 6, 1, 3, 5, 7, 9, 11],
        ];
    }
}
