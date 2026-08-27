<?php

namespace Hks\MediaKit\Html;

use Kirby\Toolkit\Html;
use Stringable;

class Source implements Stringable
{
    public function __construct(
        protected string $srcset,
        protected ?string $type = null,
        protected ?string $media = null,
        protected ?string $sizes = null,
        protected ?int $width = null,
        protected ?int $height = null,
    ) {
    }

    public static function factory(array $data): static
    {
        return new static(
            $data['srcset'],
            $data['type'] ?? null,
            $data['media'] ?? null,
            $data['sizes'] ?? null,
            $data['width'] ?? null,
            $data['height'] ?? null,
        );
    }

    public function srcset(): string
    {
        return $this->srcset;
    }

    public function type(): ?string
    {
        return $this->type;
    }

    public function media(): ?string
    {
        return $this->media;
    }

    public function sizes(): ?string
    {
        return $this->sizes;
    }

    public function width(): ?int
    {
        return $this->width;
    }

    public function height(): ?int
    {
        return $this->height;
    }

    public function toString(): string
    {
        return $this->toHtml();
    }

    public function toHtml(array $attributes = []): string
    {
        return Html::tag('source', attr: [
            ...$this->toArray(),
            ...$attributes,
        ]);
    }

    public function toArray(): array
    {
        return [
            'srcset' => $this->srcset,
            'type' => $this->type,
            'sizes' => $this->sizes,
            'media' => $this->media,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public function __toString(): string
    {
        return $this->srcset;
    }
}
