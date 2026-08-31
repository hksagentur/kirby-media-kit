<?php

namespace Hks\MediaKit\Html;

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
}
