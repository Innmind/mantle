<?php
declare(strict_types = 1);

namespace Innmind\Mantle;

use Innmind\Mantle\Source\Continuation;
use Innmind\OperatingSystem\OperatingSystem;

/**
 * @todo this should be a type defined directly in Forerunner
 * @template C
 */
interface Source
{
    /**
     * @param C $carry
     * @param Continuation<C> $continuation
     *
     * @return Continuation<C>
     */
    public function __invoke(
        mixed $carry,
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation;
}
