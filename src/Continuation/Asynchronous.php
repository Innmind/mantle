<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Continuation;

final class Asynchronous implements Strategy
{
    private function __construct()
    {
    }

    public function __invoke(): bool
    {
        return true;
    }

    public static function of(): self
    {
        return new self;
    }
}
