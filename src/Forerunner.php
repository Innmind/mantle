<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Source\Continuation;
use Innmind\OperatingSystem\OperatingSystem;

final class Forerunner
{
    private OperatingSystem $os;

    private function __construct(OperatingSystem $os)
    {
        $this->os = $os;
    }

    /**
     * @template C
     *
     * @param C $carry
     * @param callable(C, OperatingSystem, Continuation<C>): Continuation<C> $source
     *
     * @return C
     */
    public function __invoke(mixed $carry, callable $source): mixed
    {
        $tasks = Tasks::of(Source\Context::of(
            Source::of($source),
            $carry,
        ));

        while ($tasks->active()) {
            $tasks = $tasks
                ->continue($this->os)
                ->wait(Wait::new($this->os));
        }

        return $tasks->carry();
    }

    public static function of(OperatingSystem $os): self
    {
        return new self($os);
    }
}
