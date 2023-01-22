<?php
declare(strict_types = 1);

namespace Tests\Innmind\Mantle;

use Innmind\Mantle\{
    Forerunner,
    Source,
    Suspend\Asynchronous,
    Suspend\Synchronous,
};
use Innmind\Immutable\Sequence;
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

        $forerunner = Forerunner::of($strategy);
        $forerunner($source);

        $this->assertSame(
            $expected,
            \iterator_to_array($queue),
        );
    }

    public function strategies(): iterable
    {
        yield [
            null,
            [0, 2, 4, 1, 3, 6, 5, 7, 9, 11],
        ];
        yield [
            Asynchronous::of(...),
            [0, 2, 4, 1, 3, 6, 5, 7, 9, 11],
        ];
        yield [
            Synchronous::of(...),
            [0, 2, 4, 6, 1, 3, 5, 7, 9, 11],
        ];
    }
}
