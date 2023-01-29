<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Suspend\{
    Strategy,
    Asynchronous,
};
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\Sequence;

final class Forerunner
{
    private Clock $clock;
    /** @var callable(): Strategy */
    private $strategy;

    /**
     * @param callable(): Strategy $strategy
     */
    private function __construct(Clock $clock, callable $strategy)
    {
        $this->clock = $clock;
        $this->strategy = $strategy;
    }

    /**
     * @template C
     *
     * @param C $carry
     *
     * @return C
     */
    public function __invoke(mixed $carry, Source $source): mixed
    {
        /** @var Sequence<Task> */
        $active = Sequence::of();

        while ($source->active() || !$active->empty()) {
            [$carry, $emerged] = $source->emerge($carry, $active);
            $active = $active
                ->append($emerged)
                ->flatMap(fn($task) => $task->continue(
                    $this->clock,
                    $this->strategy,
                ));
        }

        return $carry;
    }

    /**
     * @param ?callable(): Strategy $strategy
     */
    public static function of(Clock $clock, callable $strategy = null): self
    {
        return new self($clock, $strategy ?? Asynchronous::of(...));
    }
}
