<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Continuation;

final class Synchronous implements Strategy
{
    public function __invoke(): bool
    {
        return false;
    }
}
