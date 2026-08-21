<?php

namespace Hks\MediaKit;

use InvalidArgumentException;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Filesystem\Asset;
use Kirby\Filesystem\Mime;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Html;
use Kirby\Toolkit\Str;
use Stringable;

class ResponsiveImage implements Stringable
{
    protected File|Asset $image;

    protected ?string $preset = null;
    protected ?int $quality = null;

    protected array $formats;
    protected array $widths;
    protected array $attributes;

    protected ?float $ratio = null;
    protected string|bool|null $crop = null;

    public function __construct(File|Asset $file)
    {
        if ($file->type() !== 'image') {
            throw new InvalidArgumentException('Unexpected file type');
        }

        $this->image = $file;

        $this->formats = $this->getPluginOptions('image.formats', ['jpeg']);
        $this->widths = $this->getPluginOptions('image.widths', ['auto']);
        $this->attributes = $this->getPluginOptions('image.attributes', []);

        $this->quality = $this->getPluginOptions('image.quality');
    }

    public static function for(File|Asset $image): static
    {
        return new static($image);
    }

    public static function from(array $options): static
    {
        return static::for($options['image'])->options($options);
    }

    public function isVectorImage(): bool
    {
        return in_array($this->image->mime(), ['image/svg+xml']);
    }

    public function isRasterImage(): bool
    {
        return ! $this->isVectorImage();
    }

    public function usePreset(): bool
    {
        return $this->getPreset() !== null;
    }

    public function options(array $options): static
    {
        if (isset($options['preset'])) {
            $this->preset($options['preset']);
        }

        if (isset($options['quality'])) {
            $this->quality($options['quality']);
        }

        if (isset($options['formats'])) {
            $this->formats($options['formats']);
        }

        if (isset($options['widths'])) {
            $this->widths($options['widths']);
        }

        if (isset($options['ratio'])) {
            $this->ratio($options['ratio']);
        }

        if (isset($options['crop'])) {
            $this->crop($options['crop']);
        }

        return $this;
    }

    public function preset(string $preset): static
    {
        $this->preset = $preset;

        return $this;
    }

    public function quality(?int $quality): static
    {
        $this->quality = $quality;

        return $this;
    }

    /** @param string[] $formats */
    public function formats(array $formats): static
    {
        $this->formats = $formats;

        return $this;
    }

    /** @param (int|string)[] $widths */
    public function widths(array $widths): static
    {
        $this->widths = array_map(function (int|string $width) {
            return $width === 'auto' ? $this->image->width() : (int) $width;
        }, array_values($widths));

        asort($this->widths, SORT_NUMERIC);

        return $this;
    }

    /** @param 'auto'|string|float|null|(int|float)[] $ratio */
    public function ratio(string|array|float|null $ratio): static
    {
        if ($ratio === 'auto') {
            $ratio = null;
        }

        if (is_string($ratio)) {
            $ratio = explode('/', $ratio, 2);
        }

        $this->ratio = match (true) {
            is_null($ratio) => null,
            is_array($ratio) => (float) $ratio[0] / (float) $ratio[1],
            default => $ratio,
        };

        return $this;
    }

    public function crop(string|bool|null $crop): static
    {
        $this->crop = $crop;

        return $this;
    }

    public function attributes(array $attributes): static
    {
        if (isset($attributes['width'])) {
            $this->width($attributes['width']);
        }

        if (isset($attributes['height'])) {
            $this->height($attributes['height']);
        }

        if (isset($attributes['alt'])) {
            $this->alt($attributes['alt']);
        }

        if (isset($attributes['id'])) {
            $this->id($attributes['id']);
        }

        if (isset($attributes['classList'])) {
            $this->classList($attributes['classList']);
        }

        if (isset($attributes['dataList'])) {
            $this->dataList($attributes['dataList']);
        }

        if (isset($attributes['sizes'])) {
            $this->sizes($attributes['sizes']);
        }

        if (isset($attributes['loading'])) {
            $this->loading($attributes['loading']);
        }

        if (isset($attributes['decoding'])) {
            $this->decoding($attributes['decoding']);
        }

        if (isset($attributes['fetchpriority'])) {
            $this->fetchPriority($attributes['fetchpriority']);
        }

        return $this;
    }

    public function id(?string $id): static
    {
        $this->attributes['id'] = $id;

        return $this;
    }

    public function width(?int $width): static
    {
        $this->attributes['width'] = $width;

        return $this;
    }

    public function height(?int $height): static
    {
        $this->attributes['height'] = $height;

        return $this;
    }

    public function alt(?string $text): static
    {
        $this->attributes['alt'] = $text;

        return $this;
    }

    /** @param string|string[]|null $styles */
    public function style(string|array|null $styles): static
    {
        $this->attributes['style'] = is_array($styles) ? A::join($styles, ';') : $styles;

        return $this;
    }

    /** @param string|string[]|null $classes */
    public function class(string|array|null $classes): static
    {
        $this->attributes['class'] = is_array($classes) ? A::join($classes, ' ') : $classes;

        return $this;
    }

    /** @param string|string[]|null $classList */
    public function classList(string|array|null $classList): static
    {
        return $this->class($classList);
    }

    /** @param ?array<string, string> $dataList */
    public function dataList(?array $dataList): static
    {
        foreach ((array) $dataList as $key => $value) {
            $this->attributes['data-' . Str::kebab($key)] = $value;
        }

        return $this;
    }

    /** @param string|string[]|null $sizes */
    public function sizes(string|array|null $sizes): static
    {
        $this->attributes['sizes'] = is_array($sizes) ? A::join($sizes) : $sizes;

        return $this;
    }

    /** @param 'auto'|'high'|'low'|null $priority */
    public function fetchPriority(?string $priority): static
    {
        $this->attributes['fetchpriority'] = $priority;

        return $this;
    }

    /** @param 'lazy'|'eager'|null $strategy */
    public function loading(?string $strategy): static
    {
        $this->attributes['loading'] = $strategy;

        return $this;
    }

    /** @param 'auto'|'sync'|'async'|null $strategy */
    public function decoding(?string $strategy): static
    {
        $this->attributes['decoding'] = $strategy;

        return $this;
    }

    /** @param ?bool $draggable */
    public function draggable(?bool $draggable = true): static
    {
        $this->attributes['draggable'] = $draggable === false ? 'false' : null;

        return $this;
    }

    public function toString(): string
    {
        return $this->toHtml();
    }

    public function toHtml(array $attributes = []): string
    {
        $attributes = [
            ...$this->attributes,
            ...$attributes,
        ];

        if (! $this->isRasterImage()) {
            return Html::img($this->image->url(), [
                'width' => $this->image->width(),
                'height' => $this->image->height(),
                'alt' => $this->image->alt(),
                ...A::without($attributes, ['srcset', 'sizes']),
            ]);
        }

        $widths = $this->getWidths();
        $formats = $this->getFormats();

        $sourceFormats = A::slice($formats, 0, -1);

        $thumbnailWidth = A::first($widths);
        $thumbnailFormat = A::last($formats);
        $thumbnailQuality = $this->getQuality($thumbnailFormat);

        $thumbnail = match (true) {
            $this->usePreset() => $this->image->thumb($this->getPresetOptions($this->getPreset(), [
                'width' => $thumbnailWidth,
                'format' => $thumbnailFormat,
                'quality' => $thumbnailQuality,
            ])),
            default => $this->image->thumb([
                'width' => $thumbnailWidth,
                'height' => $this->getHeight($thumbnailWidth),
                'crop' => $this->getCrop(),
                'format' => $thumbnailFormat,
                'quality' => $thumbnailQuality,
            ]),
        };

        $sizes = match (true) {
            $this->usePreset() => $this->getSrcsetOptions($this->getPreset(), [
                "{$thumbnailWidth}w" => [
                    'width' => $thumbnailWidth,
                ],
            ]),
            default => A::reduce($widths, fn (array $sizes, int $width) => $sizes + [
                "{$width}w" => [
                    'width' => $width,
                    'height' => $this->getHeight($width),
                    'crop' => $this->getCrop(),
                ],
            ], []),
        };

        $sizes = A::filter($sizes, function (array $options) {
            $width = $options['width'] ?? null;

            if ($width !== null && $width > $this->image->width()) {
                return false;
            }

            $height = $options['height'] ?? null;

            if ($height !== null && $height > $this->image->height()) {
                return false;
            }

            return true;
        });

        $image = Html::img($thumbnail->url(), [
            'srcset' => $this->image->srcset(A::map($sizes, fn (array $options) => [
                'quality' => $thumbnailQuality,
                ...$options,
                'format' => $thumbnailFormat,
            ])),
            'width' => $thumbnail->width(),
            'height' => $thumbnail->height(),
            'alt' => $this->image->alt(),
            ...$attributes,
        ]);

        $sources = A::map($sourceFormats, fn (string $format) => Html::tag('source', attr: [
            'srcset' => $this->image->srcset(A::map($sizes, fn (array $options) => [
                'quality' => $this->getQuality($format),
                ...$options,
                'format' => $format,
            ])),
            'type' => Mime::fromExtension($format),
            'sizes' => $attributes['sizes'] ?? null,
        ]));

        return empty($sources) ? $image : Html::tag('picture', [...$sources, $image]);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    protected function getPreset(): ?string
    {
        return $this->preset;
    }

    protected function getQuality(?string $format = null): ?int
    {
        if ($this->quality === null) {
            return null;
        }

        return match ($format) {
            'avif' => 0.6 * $this->quality,
            'webp' => 0.75 * $this->quality,
            default => $this->quality,
        };
    }

    protected function getFormats(): array
    {
        return A::map($this->formats, function (string $format) {
            return $format === 'auto' ? Str::after($this->image->mime(), '/') : $format;
        });
    }

    protected function getCrop(): string|bool|null
    {
        return $this->crop ?? ($this->ratio !== null ? true : null);
    }

    protected function getWidths(): array
    {
        $widths = A::map($this->widths, function (int|string $width) {
            return $width === 'auto' ? $this->image->width() : (int) $width;
        });

        asort($widths, SORT_NUMERIC);

        return $widths;
    }

    protected function getHeight(?int $width): ?int
    {
        if ($width === null || $this->ratio === null) {
            return null;
        }

        return (int) round($width / $this->ratio);
    }

    protected function getPluginOptions(?string $key = null, mixed $default = null): mixed
    {
        $options = App::instance()->option('hksagentur.media-kit', []);

        if (is_null($key)) {
            return $options;
        }

        return A::get($options, $key, $default);
    }

    protected function getPresetOptions(?string $preset = null, ?array $default = null): ?array
    {
        $options = App::instance()->option('thumbs.presets.' . ($preset ?? $this->getPreset()));

        if (is_array($options)) {
            return $options;
        }

        $options = App::instance()->option('thumbs.presets.default');

        if (is_array($options)) {
            return $options;
        }

        return $default;
    }

    protected function getSrcsetOptions(?string $preset = null, ?array $default = null): ?array
    {
        $options = App::instance()->option('thumbs.srcsets.' . ($preset ?? $this->getPreset()));

        if (is_array($options)) {
            return $options;
        }

        $options = App::instance()->option('thumbs.srcsets.default');

        if (is_array($options)) {
            return $options;
        }

        return $default;
    }
}
