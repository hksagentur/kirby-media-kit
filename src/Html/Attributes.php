<?php

namespace Hks\MediaKit\Html;

use ArrayIterator;
use Closure;
use Hks\MediaKit\Toolkit\TokenList;
use IteratorAggregate;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Dom;
use Kirby\Toolkit\Html;
use Stringable;
use Traversable;

class Attributes implements IteratorAggregate, Stringable
{
    protected const BOOLEAN_ATTRIBUTES = [
        'autoplay',
        'controls',
        'loop',
        'muted',
        'playsinline',
        'disablepictureinpicture',
        'disableremoteplayback',
        'inert',
        'ismap',
    ];

    protected const TOKEN_LIST_ATTRIBUTES = [
        'class' => ' ',
        'style' => '; ',
        'sizes' => ', ',
        'controlslist' => ' ',
        'aria-describedby' => ' ',
        'aria-labelledby' => ' ',
    ];

    protected const TRUE_FALSE_ATTRIBUTES = [
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

    public function isEmpty(): bool
    {
        return array_filter($this->data, fn (mixed $value) => $value !== null) === [];
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function has(string $name): bool
    {
        return ($this->data[$name] ?? null) !== null;
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
            in_array($name, static::TRUE_FALSE_ATTRIBUTES) => static::normalizeTrueFalseValue($value),
            in_array($name, static::BOOLEAN_ATTRIBUTES) => static::normalizeBooleanValue($value),
            default => $value,
        };

        return $this;
    }

    public function remove(string ...$names): static
    {
        foreach ($names as $name) {
            unset($this->data[$name]);
        }

        return $this;
    }

    /** @param string|string[] $value */
    public function append(string $name, string|array $value): static
    {
        $values = array_filter(A::wrap($value));

        if (empty($values)) {
            return $this;
        }

        return $this->set($name, [
            ...array_filter([$this->get($name)]),
            ...$values,
        ]);
    }

    /** @param string|array<int|string, mixed>|null $classes */
    public function class(string|array|null $classes): static
    {
        return $this->append('class', static::compileTokens(A::wrap($classes)));
    }

    /** @param string|array<int|string, mixed>|null $styles */
    public function style(string|array|null $styles): static
    {
        return $this->append('style', static::compileTokens(A::wrap($styles)));
    }

    public function only(string ...$names): static
    {
        $attributes = $this->clone();
        $attributes->data = array_intersect_key($this->data, array_flip($names));

        return $attributes;
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
        return $this->clone()->remove(...$names);
    }

    public function clone(): static
    {
        return clone $this;
    }

    public function cloneWith(self|array $attributes): static
    {
        return $this->clone()->merge($attributes);
    }

    /**
     * @deprecated 2.3.0 Use `without()` instead, which returns a copy since 2.3.0.
     */
    public function cloneWithout(string ...$names): static
    {
        return $this->without(...$names);
    }

    /**
     * @template TValue
     * @param TValue $value
     * @param callable(static, TValue): mixed $callback
     * @param (callable(static, TValue): mixed)|null $default
     */
    public function when(mixed $value, callable $callback, ?callable $default = null): mixed
    {
        if ($value) {
            return $callback($this, $value) ?? $this;
        }

        if ($default !== null) {
            return $default($this, $value) ?? $this;
        }

        return $this;
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

    /**
     * @param array<int|string, mixed> $values
     * @return string[]
     */
    protected static function compileTokens(array $values): array
    {
        $tokens = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && $value) {
                $tokens[] = $key;
            } elseif (is_string($value) && trim($value) !== '') {
                $tokens[] = $value;
            }
        }

        return $tokens;
    }

    /** @param string|string[]|null $value */
    protected static function normalizeTokenListValue(string|array|null $value, string $separator): ?string
    {
        return TokenList::from(A::wrap($value), $separator)->value();
    }

    protected static function normalizeTrueFalseValue(bool|string|null $value): ?string
    {
        return match ($value) {
            true => 'true',
            false => 'false',
            default => $value,
        };
    }

    protected static function normalizeBooleanValue(mixed $value): Closure|true|null
    {
        return match (true) {
            $value instanceof Closure => $value,
            $value === false, $value === null => null,
            default => true,
        };
    }
}
