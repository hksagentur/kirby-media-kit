<?php

namespace Hks\MediaKit\Html;

use Closure;
use Kirby\Toolkit\Collection as ToolkitCollection;

/**
 * @template T of Element
 * @extends ToolkitCollection<T>
 */
abstract class Collection extends ToolkitCollection
{
    public const ITEM_CLASS = Element::class;

    public static function factory(?array $items = null): static
    {
        if (empty($items) || ! is_array($items)) {
            return new static();
        }

        $class = static::ITEM_CLASS;
        $collection = new static();

        foreach ($items as $item) {
            $collection->add($class::factory($item));
        }

        return $collection;
    }

    /** @param T $item */
    public function add(Element $item): static
    {
        return $this->append($item);
    }

    public function html(array $attributes = []): string
    {
        return $this->toHtml($attributes);
    }

    public function toString(): string
    {
        return implode("\n", $this->toArray(fn (Element $item) => $item->toString()));
    }

    public function toHtml(array $attributes = []): string
    {
        return implode("\n", $this->toArray(fn (Element $item) => $item->toHtml($attributes)));
    }

    public function toArray(?Closure $callback = null): array
    {
        return parent::toArray($callback ?? fn (Element $item) => $item->toArray());
    }
}
