<?php

namespace Hks\MediaKit\Html;

use ArrayIterator;
use Closure;
use IteratorAggregate;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Dom;
use Kirby\Toolkit\Html;
use Stringable;
use Traversable;

class Attributes implements IteratorAggregate, Stringable
{
    protected const TOKEN_LIST_ATTRIBUTES = [
        'class' => ' ',
        'style' => ';',
        'sizes' => ', ',
        'controlslist' => ' ',
        'aria-describedby' => ' ',
        'aria-labelledby' => ' ',
    ];

    protected const TRISTATE_ATTRIBUTES = [
        'draggable',
        'aria-hidden',
    ];

    protected array $data = [];

    public function __construct(array $attributes = [])
    {
        $this->merge($attributes);
    }

    /** @param self|array|string|null $attributes */
    public static function from(self|array|string|null $attributes): static
    {
        return match (true) {
            $attributes instanceof self => $attributes,
            is_null($attributes) => new static(),
            is_string($attributes) => static::parse($attributes),
            default => new static($attributes),
        };
    }

    public static function parse(string $attributes): static
    {
        $node = (new Dom('<x ' . $attributes . '>'))
            ->document()
            ->getElementsByTagName('x')
            ->item(0);

        $parsed = [];

        foreach ($node?->attributes ?? [] as $attribute) {
            $parsed[$attribute->nodeName] = $attribute->nodeValue !== $attribute->nodeName
                ? $attribute->nodeValue
                : true;
        }

        return new static($parsed);
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function missing(string $name): bool
    {
        return ! $this->has($name);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        $value = $this->data[$name] ?? null;

        if ($value === null) {
            return $default instanceof Closure ? $default() : $default;
        }

        return $value instanceof Closure ? $value() : $value;
    }

    public function set(string $name, mixed $value): static
    {
        $this->data[$name] = match (true) {
            array_key_exists($name, static::TOKEN_LIST_ATTRIBUTES) => static::normalizeTokenListValue($value, static::TOKEN_LIST_ATTRIBUTES[$name]),
            in_array($name, static::TRISTATE_ATTRIBUTES) => static::normalizeTristateValue($value),
            default => $value,
        };

        return $this;
    }

    public function merge(self|array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            $this->set($name, $value);
        }

        return $this;
    }

    public function mergeIfMissing(self|array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            if ($this->missing($name)) {
                $this->set($name, $value);
            }
        }

        return $this;
    }

    public function without(string ...$names): static
    {
        foreach ($names as $name) {
            unset($this->data[$name]);
        }

        return $this;
    }

    public function clone(): static
    {
        return clone $this;
    }

    public function cloneWith(self|array $attributes): static
    {
        return $this->clone()->merge($attributes);
    }

    public function cloneWithout(string ...$names): static
    {
        return $this->clone()->without(...$names);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function toString(): string
    {
        return $this->toHtml();
    }

    public function toHtml(): string
    {
        return Html::attr($this->data) ?? '';
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function __call(string $name, array $arguments): mixed
    {
        if ($arguments === []) {
            return $this->get($name);
        }

        return $this->set($name, ...$arguments);
    }

    /** @param string|string[]|null $value */
    protected static function normalizeTokenListValue(string|array|null $value, string $separator): ?string
    {
        return is_array($value) ? A::join($value, $separator) : $value;
    }

    protected static function normalizeTristateValue(?bool $value): ?string
    {
        return $value === null ? null : ($value ? 'true' : 'false');
    }
}
