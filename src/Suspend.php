<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Suspend\Strategy;

final class Suspend
{
    private Strategy $shouldSuspend;

    private function __construct(Strategy $shouldSuspend)
    {
        $this->shouldSuspend = $shouldSuspend;
    }

    public function __invoke(): void
    {
        if (($this->shouldSuspend)()) {
            \Fiber::suspend();
        }
    }

    public static function of(Strategy $shouldSuspend): self
    {
        return new self($shouldSuspend);
    }
}
