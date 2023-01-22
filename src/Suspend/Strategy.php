<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Suspend;

interface Strategy
{
    /**
     * Return true when you want the thread to be suspended
     */
    public function __invoke(): bool;
}
