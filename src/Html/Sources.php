<?php

namespace Hks\MediaKit\Html;

use Closure;
use Kirby\Toolkit\Collection;

/**
 * @extends Collection<Source>
 */
class Sources extends Collection
{
    public static function factory(?array $items = null): static
    {
        if (empty($items) || ! is_array($items)) {
            return new static();
        }

        $sources = new static();

        foreach ($items as $item) {
            $sources->add(Source::factory($item));
        }

        return $sources;
    }

    public function add(Source $source): static
    {
        return $this->append($source);
    }

    public function toString(): string
    {
        return implode("\n", $this->toArray(fn (Source $source) => $source->toString()));
    }

    public function toHtml(array $attributes = []): string
    {
        return implode("\n", $this->toArray(fn (Source $source) => $source->toHtml($attributes)));
    }

    public function toArray(?Closure $callback = null): array
    {
        return parent::toArray($callback ?? fn (Source $source) => $source->toArray());
    }
}
