<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Continuation\Strategy;

final class Continuation
{
    private Strategy $shouldSuspend;

    private function __construct(Strategy $shouldSuspend)
    {
        $this->shouldSuspend = $shouldSuspend;
    }

    public static function of(Strategy $shouldSuspend): self
    {
        return new self($shouldSuspend);
    }

    public function suspend(): void
    {
        if (($this->shouldSuspend)()) {
            \Fiber::suspend();
        }
    }
}
