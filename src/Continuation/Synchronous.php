<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Continuation;

final class Synchronous implements Strategy
{
    private function __construct()
    {
    }

    public function __invoke(): bool
    {
        return false;
    }

    public static function of(): self
    {
        return new self;
    }
}
