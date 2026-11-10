<?php

namespace Hks\MediaKit\Toolkit;

use Kirby\Toolkit\Str;
use Stringable;

class TokenList implements Stringable
{
    /** @param string[] $tokens */
    protected function __construct(
        protected array $tokens,
        protected string $separator = ' ',
    ) {
    }

    public static function parse(string $value, string $separator): static
    {
        return new static(Str::split($value, static::normalizeSeparator($separator)), $separator);
    }

    /** @param string|mixed[]|null $tokens */
    public static function from(string|array|null $tokens, string $separator): static
    {
        if (! is_array($tokens)) {
            return static::parse($tokens ?? '', $separator);
        }

        $characters = static::normalizeSeparator($separator) . " \t\n\r\0\x0B";

        $tokens = array_map(
            fn (string $token) => trim($token, $characters),
            array_filter($tokens, is_string(...)),
        );

        return new static(array_values(array_filter($tokens, fn (string $token) => $token !== '')), $separator);
    }

    /** @param string|mixed[]|null $tokens */
    public static function spaceSeparated(string|array|null $tokens): static
    {
        return static::from($tokens, ' ');
    }

    /** @param string|mixed[]|null $tokens */
    public static function commaSeparated(string|array|null $tokens): static
    {
        return static::from($tokens, ', ');
    }

    public function isEmpty(): bool
    {
        return $this->tokens === [];
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function value(): ?string
    {
        return $this->isNotEmpty() ? $this->toString() : null;
    }

    public function valueOr(string $fallback): string
    {
        return $this->value() ?? $fallback;
    }

    public function prepend(string ...$tokens): static
    {
        return new static([...$tokens, ...$this->tokens], $this->separator);
    }

    public function append(string ...$tokens): static
    {
        return new static([...$this->tokens, ...$tokens], $this->separator);
    }

    public function without(string ...$tokens): static
    {
        return new static(array_values(array_diff($this->tokens, $tokens)), $this->separator);
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

    /** @return string[] */
    public function toArray(): array
    {
        return $this->tokens;
    }

    public function toString(): string
    {
        return implode($this->separator, $this->tokens);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    protected static function normalizeSeparator(string $separator): string
    {
        return trim($separator) ?: ' ';
    }
}
