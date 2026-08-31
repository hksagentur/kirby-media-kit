<?php

namespace Hks\MediaKit\Html;

use Kirby\Toolkit\Html;
use ReflectionClass;
use Stringable;

abstract class Element implements Stringable
{
    public function __construct(
        protected Attributes $attributes,
    ) {
    }

    abstract public static function factory(array $data): static;

    public function tag(): string
    {
        return strtolower((new ReflectionClass($this))->getShortName());
    }

    public function attributes(): Attributes
    {
        return $this->attributes;
    }

    public function toString(): string
    {
        return $this->toHtml();
    }

    public function toHtml(array $attributes = []): string
    {
        return Html::tag(
            name: $this->tag(),
            attr: $this->attributes()
                ->cloneWith($attributes)
                ->toArray(),
        );
    }

    public function toArray(): array
    {
        return $this->attributes->toArray();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
