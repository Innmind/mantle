<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

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
     * @param Source<C> $source
     *
     * @return C
     */
    public function __invoke(mixed $carry, Source $source): mixed
    {
        $tasks = Tasks::of(Source\Context::of($source, $carry));

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
