<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Suspend\Action;

/**
 * @internal
 */
final class Suspend
{
    private function __construct()
    {
    }

    /**
     * @template T
     *
     * @param Action<T> $action
     *
     * @return T
     */
    public function __invoke(Action $action): mixed
    {
        /** @var T */
        return \Fiber::suspend($action);
    }

    public static function new(): self
    {
        return new self;
    }
}
