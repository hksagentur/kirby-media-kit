<?php

namespace Hks\MediaKit\Cms;

class Modifications
{
    /** @var array<string, mixed> */
    protected array $data = [];

    public function isEmpty(): bool
    {
        return ! $this->hasAny(array_keys($this->data));
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /** @param string[] $keys */
    public function hasAny(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }

        return false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }
}
