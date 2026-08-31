<?php

namespace Hks\MediaKit\Cms;

trait HasModifications
{
    /** @var array<string, mixed> */
    protected array $modifications = [];

    /** @param string|string[]|null $keys */
    protected function isModified(string|array|null $keys = null): bool
    {
        if ($keys !== null) {
            return $this->hasModifications((array) $keys);
        }

        return $this->modifications !== [];
    }

    /** @param string[] $keys */
    protected function hasModifications(array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->modifications)) {
                return true;
            }
        }

        return false;
    }

    protected function modification(string $key, mixed $default = null): mixed
    {
        return $this->modifications[$key] ?? $default;
    }

    protected function modify(string $key, mixed $value): static
    {
        $this->modifications[$key] = $value;

        return $this;
    }
}
