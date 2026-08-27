<?php

namespace Hks\MediaKit;

use Hks\MediaKit\Html\Attributes;
use Hks\MediaKit\Html\Sources;
use Kirby\Cms\App;
use Stringable;

class ResponsiveImage implements Stringable
{
    public function __construct(
        protected ?Sources $sources = null,
        protected ?Attributes $attributes = null,
    ) {
        $this->sources ??= new Sources();
        $this->attributes ??= new Attributes();
    }

    public function hasAttributes(): bool
    {
        return $this->attributes->isNotEmpty();
    }

    public function hasAttribute(string $name): bool
    {
        return $this->attributes->has($name);
    }

    public function hasSources(): bool
    {
        return $this->sources->isNotEmpty();
    }

    public function hasSource(string $type): bool
    {
        return $this->sources->findBy('type', $type) !== null;
    }

    public function attributes(): Attributes
    {
        return $this->attributes;
    }

    public function sources(): Sources
    {
        return $this->sources;
    }

    public function render(array $data = [], array $attributes = []): string
    {
        return App::instance()->snippet('media-kit/image', [
            ...$data,
            'sources' => $this->sources,
            'attributes' => $this->attributes->cloneWith($attributes),
        ], return: true);
    }

    public function toString(): string
    {
        return $this->render();
    }

    public function toHtml(array $attributes = []): string
    {
        return $this->render(attributes: $attributes);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
