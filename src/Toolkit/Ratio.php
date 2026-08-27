<?php

namespace Hks\MediaKit\Toolkit;

use Stringable;

class Ratio implements Stringable
{
    protected function __construct(
        protected float $width,
        protected float $height,
    ) {
    }

    /** @param string|float|null|(int|float)[] $ratio */
    public static function from(string|array|float|null $ratio): ?static
    {
        return match (true) {
            is_null($ratio) => null,
            is_string($ratio) => static::parse($ratio),
            is_array($ratio) => new static((float) $ratio[0], (float) $ratio[1]),
            default => new static($ratio, 1.0),
        };
    }

    /** @param string|float|null|(int|float)[]|self $ratio */
    public static function wrap(string|array|float|null|self $ratio): ?static
    {
        return $ratio instanceof self ? $ratio : static::from($ratio);
    }

    public static function parse(string $ratio): static
    {
        $parts = explode('/', $ratio, 2);

        return new static((float) $parts[0], (float) $parts[1]);
    }

    public function width(): float
    {
        return $this->width;
    }

    public function height(): float
    {
        return $this->height;
    }

    public function toFloat(): float
    {
        return $this->width / $this->height;
    }

    public function toString(): string
    {
        return implode('/', $this->toPair());
    }

    /** @return array{0: float, 1: float} */
    public function toPair(): array
    {
        return [$this->width, $this->height];
    }

    /** @return array{width: float, height: float} */
    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
