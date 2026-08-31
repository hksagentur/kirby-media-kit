<?php

namespace Hks\MediaKit\Cms;

use Hks\MediaKit\Toolkit\Ratio;

class ThumbnailOptions
{
    protected ?Ratio $ratio = null;
    protected string|bool|null $crop = null;

    public function __construct(
        protected array $options = []
    ) {
    }

    public function width(): ?int
    {
        return $this->options['width'] ?? null;
    }

    public function height(): ?int
    {
        $width = $this->width();

        if ($width === null || $this->ratio === null) {
            return null;
        }

        return (int) round($width / $this->ratio->toFloat());
    }

    public function ratio(string|array|float|null|Ratio $ratio): static
    {
        $this->ratio = Ratio::wrap($ratio);

        return $this;
    }

    public function crop(string|bool|null $crop): static
    {
        $this->crop = $crop;

        return $this;
    }

    public function merge(array $values): static
    {
        foreach ($values as $key => $value) {
            if ($value !== null) {
                $this->options[$key] = $value;
            }
        }

        return $this;
    }

    public function fits(int $maxWidth, int $maxHeight): bool
    {
        $width = $this->width();

        if ($width !== null && $width > $maxWidth) {
            return false;
        }

        $height = $this->height();

        if ($height !== null && $height > $maxHeight) {
            return false;
        }

        return true;
    }

    public function toArray(): array
    {
        $crop = $this->crop ?? ($this->ratio !== null ? true : null);

        $width = $this->width();
        $height = $this->height();

        return [
            ...$this->options,
            ...($crop !== null) ? ['crop' => $crop] : [],
            ...($width !== null) ? ['width' => $width] : [],
            ...($height !== null) ? ['height' => $height] : [],
        ];
    }
}
