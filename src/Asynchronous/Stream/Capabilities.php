<?php
declare(strict_types = 1);

namespace Innmind\Mantle\Asynchronous\Stream;

use Innmind\Mantle\Suspend;
use Innmind\Stream\{
    Capabilities as CapabilitiesInterface,
};

/**
 * @internal
 */
final class Capabilities implements CapabilitiesInterface
{
    private Suspend $suspend;
    private CapabilitiesInterface $original;

    private function __construct(
        Suspend $suspend,
        CapabilitiesInterface $original,
    ) {
        $this->suspend = $suspend;
        $this->original = $original;
    }

    public static function of(
        Suspend $suspend,
        CapabilitiesInterface $original,
    ): self {
        return new self($suspend, $original);
    }

    public function temporary(): CapabilitiesInterface\Temporary
    {
        return $this->original->temporary();
    }

    public function readable(): CapabilitiesInterface\Readable
    {
        return $this->original->readable();
    }

    public function writable(): CapabilitiesInterface\Writable
    {
        return $this->original->writable();
    }

    public function watch(): CapabilitiesInterface\Watch
    {
        return Capabilities\Watch::of($this->suspend);
    }
}
