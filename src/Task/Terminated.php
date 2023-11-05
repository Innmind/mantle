<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Task;

/**
 * @internal
 * @psalm-immutable
 * @template T
 */
final class Terminated
{
    /** @var T */
    private mixed $returned;

    /**
     * @param T $returned
     */
    private function __construct(mixed $returned)
    {
        $this->returned = $returned;
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param A $returned
     *
     * @return self<A>
     */
    public static function of(mixed $returned): self
    {
        return new self($returned);
    }

    /**
     * @return T
     */
    public function returned(): mixed
    {
        return $this->returned;
    }
}
