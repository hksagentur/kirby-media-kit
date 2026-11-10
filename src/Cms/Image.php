<?php

namespace Hks\MediaKit\Cms;

use Hks\MediaKit\Html\Attributes;
use Hks\MediaKit\Html\Source;
use Hks\MediaKit\Html\Sources;
use Hks\MediaKit\Toolkit\TokenList;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Filesystem\Asset;
use Stringable;

class Image implements Stringable
{
    public function __construct(
        protected File|Asset $original,
        protected Sources $sources = new Sources(),
        protected Attributes $attributes = new Attributes(),
        protected bool $priority = false,
    ) {
    }

    public function hasSources(): bool
    {
        return $this->sources->isNotEmpty();
    }

    public function hasSource(string $type): bool
    {
        return $this->sources->findBy('type', $type) !== null;
    }

    public function hasAttributes(): bool
    {
        return $this->attributes->isNotEmpty();
    }

    public function hasAttribute(string $name): bool
    {
        return $this->attributes->has($name);
    }

    public function hasPriority(): bool
    {
        return $this->priority;
    }

    public function original(): File|Asset
    {
        return $this->original;
    }

    public function sources(): Sources
    {
        return $this->sources;
    }

    public function attributes(): Attributes
    {
        return $this->attributes;
    }

    public function id(): ?string
    {
        return $this->attributes->get('id');
    }

    public function width(): ?int
    {
        return $this->attributes->get('width');
    }

    public function height(): ?int
    {
        return $this->attributes->get('height');
    }

    public function alt(): ?string
    {
        return $this->attributes->get('alt');
    }

    public function html(array $attributes = []): string
    {
        return $this->render(attributes: $attributes);
    }

    public function render(array $data = [], array $attributes = []): string
    {
        return App::instance()->snippet('media-kit/image', [
            ...$data,
            'image' => $this,
            'attributes' => $this->attributes->cloneWith($attributes),
        ], return: true);
    }

    public function toArray(): array
    {
        return $this->attributes->toArray();
    }

    public function toString(): string
    {
        return $this->render();
    }

    public function toHtml(array $attributes = []): string
    {
        return $this->render(attributes: $attributes);
    }

    public function toPreloadLink(array $attributes = []): string
    {
        $source = $this->sources->first() ?? Source::factory(
            $this->attributes
                ->only('src', 'srcset', 'sizes')
                ->all(),
        );

        return $source->toPreloadLink([
            'imagesrcset' => $source->srcset(),
            'imagesizes' => $this->resolvePreloadSizes($source),
            ...$this->attributes->only('fetchpriority', 'crossorigin', 'referrerpolicy'),
            ...$attributes,
        ]);
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->original->$method(...$arguments);
    }

    protected function resolvePreloadSizes(Source $candidate): ?string
    {
        return TokenList::commaSeparated($candidate->sizes())
            ->without('auto')
            ->value();
    }
}
