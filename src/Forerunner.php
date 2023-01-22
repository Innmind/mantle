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

    public function __invoke(Source $source): void
    {
        /** @var Sequence<Task> */
        $active = Sequence::of();

        while ($source->active() || !$active->empty()) {
            $active = $active
                ->append($source->emerge($active))
                ->flatMap(fn($task) => $task->continue($this->strategy));
        }
    }

    /**
     * @param ?callable(): Strategy $strategy
     */
    public static function of(callable $strategy = null): self
    {
        return new self($strategy ?? Asynchronous::of(...));
    }
}
