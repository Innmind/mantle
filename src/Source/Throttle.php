<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Source;

use Innmind\Mantle\{
    Source,
    Task,
};
use Innmind\Immutable\Sequence;

final class Throttle implements Source
{
    private Source $source;
    /** @var positive-int */
    private int $limit;
    /** @var Sequence<Task> */
    private Sequence $buffer;

    /**
     * @param positive-int $limit
     */
    private function __construct(Source $source, int $limit)
    {
        $this->source = $source;
        $this->limit = $limit;
        $this->buffer = Sequence::of();
    }

    /**
     * @param positive-int $limit
     */
    public static function of(Source $source, int $limit): self
    {
        return new self($source, $limit);
    }

    public function emerge(Sequence $active): Sequence
    {
        if ($active->size() >= $this->limit) {
            return Sequence::of();
        }

        /** @var positive-int */
        $letThrough = $this->limit - $active->size();
        $buffer = $this->buffer;

        if ($buffer->size() < $letThrough) {
            $buffer = $buffer->append($this->source->emerge($active));
        }

        $emerge = $buffer->take($letThrough);
        $this->buffer = $buffer->drop($letThrough);

        return $emerge;
    }

    public function active(): bool
    {
        return !$this->buffer->empty() || $this->source->active();
    }
}
