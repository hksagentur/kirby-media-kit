<?php

namespace Hks\MediaKit\Html;

use Kirby\Filesystem\F;
use Kirby\Http\Url;
use Kirby\Toolkit\Html;
use Kirby\Toolkit\Str;

class Source extends Element
{
    public static function factory(array $data): static
    {
        return new static(Attributes::from([
            'type' => $data['type'] ?? null,
            'media' => $data['media'] ?? null,
            'src' => $data['src'] ?? null,
            'srcset' => $data['srcset'] ?? null,
            'sizes' => $data['sizes'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
        ]));
    }

    public function type(): ?string
    {
        return $this->attributes->get('type');
    }

    public function media(): ?string
    {
        return $this->attributes->get('media');
    }

    public function src(): ?string
    {
        return $this->attributes->get('src');
    }

    public function srcset(): ?string
    {
        return $this->attributes->get('srcset');
    }

    public function sizes(): ?string
    {
        return $this->attributes->get('sizes');
    }

    public function width(): ?int
    {
        return $this->attributes->get('width');
    }

    public function height(): ?int
    {
        return $this->attributes->get('height');
    }

    public function toPreloadLink(array $attributes = []): string
    {
        return Html::tag('link', attr: [
            'rel' => 'preload',
            'as' => $this->preloadAs(),
            'type' => $this->type(),
            'media' => $this->media(),
            'href' => $this->src(),
            ...$attributes,
        ]);
    }

    protected function preloadAs(): ?string
    {
        $type = match (true) {
            $this->type() !== null => Str::before($this->type(), '/'),
            $this->src() !== null => F::type(Url::path($this->src())),
            default => null,
        };

        return in_array($type, ['image', 'video', 'audio']) ? $type : null;
    }
}
