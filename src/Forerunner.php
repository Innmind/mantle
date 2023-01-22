<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Suspend\{
    Strategy,
    Asynchronous,
};
use Innmind\Immutable\Sequence;

final class Forerunner
{
    /** @var callable(): Strategy */
    private $strategy;

    /**
     * @param callable(): Strategy $strategy
     */
    private function __construct(callable $strategy)
    {
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
                ->flatMap(fn($task) => $task->continue($this->strategy));
        }

        return $carry;
    }

    /**
     * @param ?callable(): Strategy $strategy
     */
    public static function of(callable $strategy = null): self
    {
        return new self($strategy ?? Asynchronous::of(...));
    }
}
