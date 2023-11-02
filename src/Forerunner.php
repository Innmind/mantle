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
     *
     * @return C
     */
    public function __invoke(mixed $carry, Source $source): mixed
    {
        $tasks = Tasks::none();
        $active = $tasks->active();

        while ($source->active() || !$active->empty()) {
            [$carry, $emerged] = $source->emerge($carry, $active);
            $tasks = $tasks
                ->append($emerged)
                ->continue($this->os)
                ->wait(Wait::new($this->os));
            $active = $tasks->active();
        }

        return $carry;
    }

    public static function of(OperatingSystem $os): self
    {
        return new self($os);
    }
}
