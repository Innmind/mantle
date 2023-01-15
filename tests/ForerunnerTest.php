<?php
declare(strict_types = 1);

namespace Tests\Innmind\Mantle;

use Innmind\Mantle\{
    Forerunner,
    Source,
    Continuation\Asynchronous,
    Continuation\Synchronous,
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
            static fn($continuation) => Sequence::lazy(static function() use ($continuation) {
                yield 0;
                $continuation->suspend();
                yield 2;
                $continuation->suspend();
                yield 4;
                $continuation->suspend();
                yield 6;
                $continuation->suspend();
            })
                ->foreach(static fn($i) => $queue->enqueue($i)),
            static fn($continuation) => Sequence::lazy(static function() use ($continuation) {
                yield 1;
                $continuation->suspend();
                yield 3;
                $continuation->suspend();
                yield 5;
                $continuation->suspend();
                yield 7;
                $continuation->suspend();
                yield 9;
                $continuation->suspend();
                yield 11;
                $continuation->suspend();
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
            static fn() => new Asynchronous,
            [0, 2, 4, 1, 3, 6, 5, 7, 9, 11],
        ];
        yield [
            static fn() => new Synchronous,
            [0, 2, 4, 6, 1, 3, 5, 7, 9, 11],
        ];
    }
}
