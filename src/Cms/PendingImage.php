<?php

namespace Hks\MediaKit\Cms;

use Hks\MediaKit\Html\Attributes;
use Hks\MediaKit\Html\Sources;
use InvalidArgumentException;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\FileVersion;
use Kirby\Filesystem\Asset;
use Kirby\Filesystem\Mime;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\Str;
use Stringable;

class PendingImage implements Stringable
{
    use HasModifications;

    protected File|Asset $image;
    protected Attributes $attributes;

    /** @var string|array<string, mixed> */
    protected string|array $preset = 'default';

    /** @var string[] */
    protected array $formats = [];

    /** @var (int|'auto')[] */
    protected array $widths = [];

    public function __construct(File|Asset $file, array $options = [])
    {
        if ($file->type() !== 'image') {
            throw new InvalidArgumentException('Unexpected file type');
        }

        $this->image = $file;

        $this->optionsFromConfig();
        $this->optionsFromProps($options);
    }

    public static function for(File|Asset $image, array $options = []): static
    {
        return new static($image, $options);
    }

    public static function from(array $options): static
    {
        return static::for($options['image'], $options);
    }

    /** @param string|array<string, mixed> $preset */
    public function preset(string|array $preset): static
    {
        return $this->modify('preset', $this->preset = $preset);
    }

    /** @param 'auto'|int|null $quality */
    public function quality(string|int|null $quality): static
    {
        return $this->modify('quality', $quality);
    }

    /** @param string[] $formats */
    public function formats(array $formats): static
    {
        return $this->modify('formats', $this->formats = $formats);
    }

    /** @param (int|'auto')[] $widths */
    public function widths(array $widths): static
    {
        return $this->modify('widths', $this->widths = $widths);
    }

    public function width(?int $width): static
    {
        $this->attributes->set('width', $width);
        return $this->modify('width', $width);
    }

    public function height(?int $height): static
    {
        $this->attributes->set('height', $height);
        return $this->modify('height', $height);
    }

    /** @param 'auto'|string|float|null|array{0: int|float, 1: int|float} $ratio */
    public function ratio(string|array|float|null $ratio): static
    {
        return $this->modify('ratio', ! in_array($ratio, ['auto', ''], true) ? $ratio : null);
    }

    /** @param 'top'|'top left'|'top right'|'left'|'center'|'right'|'bottom'|'bottom left'|'bottom right'|string|bool|null $crop */
    public function crop(string|bool|null $crop): static
    {
        return $this->modify('crop', $crop);
    }

    /** @param array<string, mixed> $attributes */
    public function attributes(array $attributes): static
    {
        $this->attributes->merge($attributes);

        return $this;
    }

    public function id(?string $id): static
    {
        $this->attributes->set('id', $id);

        return $this;
    }

    /** @param string|string[]|null $classes */
    public function class(string|array|null $classes): static
    {
        $this->attributes->set('class', $classes);

        return $this;
    }

    /** @param string|string[]|null $styles */
    public function style(string|array|null $styles): static
    {
        $this->attributes->set('style', $styles);

        return $this;
    }

    public function alt(?string $text): static
    {
        $this->attributes->set('alt', $text);

        return $this;
    }

    /** @param string|string[]|null $sizes */
    public function sizes(string|array|null $sizes): static
    {
        $this->attributes->set('sizes', $sizes);

        return $this;
    }

    /** @param 'auto'|'high'|'low'|null $priority */
    public function fetchPriority(?string $priority): static
    {
        $this->attributes->set('fetchpriority', $priority);

        return $this;
    }

    /** @param 'lazy'|'eager'|null $strategy */
    public function loading(?string $strategy): static
    {
        $this->attributes->set('loading', $strategy);

        return $this;
    }

    /** @param 'auto'|'sync'|'async'|null $strategy */
    public function decoding(?string $strategy): static
    {
        $this->attributes->set('decoding', $strategy);

        return $this;
    }

    public function draggable(?bool $draggable = true): static
    {
        $this->attributes->set('draggable', $draggable);

        return $this;
    }

    /** @param 'anonymous'|'use-credentials'|null $crossOrigin */
    public function crossOrigin(?string $crossOrigin): static
    {
        $this->attributes->set('crossorigin', $crossOrigin);

        return $this;
    }

    public function generate(): Image
    {
        if (! $this->image->isResizable()) {
            return new Image($this->image, new Sources(), $this->attributes->clone()->mergeIfMissing([
                'src' => $this->image->url(),
                'width' => $this->image->width(),
                'height' => $this->image->height(),
                'alt' => $this->image->alt(),
            ])->without('srcset', 'sizes'));
        }

        $loading = $this->attributes->get('loading');

        $srcset = $this->attributes->get('srcset', fn () => $this->resolveFallbackSrcset());
        $sizes = $this->attributes->get('sizes', fn () => $this->resolveFallbackSizes($loading));

        $thumbnail = $this->generateThumbnail();
        $sources = $this->generateSources($sizes);

        return new Image($this->image, $sources, $this->attributes->clone()->mergeIfMissing([
            'src' => $thumbnail->url(),
            'srcset' => $srcset,
            'sizes' => $sizes,
            'width' => $thumbnail->width(),
            'height' => $thumbnail->height(),
            'alt' => $this->image->alt(),
        ]));
    }

    public function toString(): string
    {
        return $this->toHtml();
    }

    public function toHtml(array $attributes = []): string
    {
        return $this->generate()->toHtml($attributes);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function __call(string $method, array $arguments): static
    {
        $this->attributes->set(strtolower($method), $arguments === [] ? true : $arguments[0]);

        return $this;
    }

    /** @param 'jpeg'|'png'|'avif'|'webp' $format */
    protected function generateSrcset(string $format): ?string
    {
        $sizes = $this->resolveSizes($format);

        if ($sizes === []) {
            return null;
        }

        return $this->image->srcset($sizes);
    }

    protected function generateThumbnail(): File|FileVersion|Asset
    {
        $format = $this->resolveFallbackFormat();
        $options = $this->resolveThumbOptions($format);

        return $this->image->thumb($options->toArray());
    }

    protected function generateSources(?string $sizes = null): Sources
    {
        $items = [];

        foreach (array_slice($this->resolveFormats(), 0, -1) as $format) {
            if ($srcset = $this->generateSrcset($format)) {
                $items[] = [
                    'type' => Mime::fromExtension($format),
                    'sizes' => $sizes,
                    'srcset' => $srcset,
                ];
            }
        }

        return Sources::factory($items);
    }

    /**
     * @param array<string, mixed> $default
     * @return array<string, mixed>
     */
    protected function loadPreset(string $name, array $default = []): array
    {
        $options = App::instance()->option("thumbs.presets.{$name}")
            ?? App::instance()->option('thumbs.presets.default');

        return is_array($options) ? $options : $default;
    }

    /**
     * @param array<string, array<string, mixed>> $default
     * @return array<string, array<string, mixed>>
     */
    protected function loadSrcsetPreset(string $name, array $default = []): array
    {
        $entries = App::instance()->option("thumbs.srcsets.{$name}")
            ?? App::instance()->option('thumbs.srcsets.default');

        return is_array($entries) ? $entries : $default;
    }

    protected function usesNamedPreset(): bool
    {
        return is_string($this->preset);
    }

    protected function usesSrcsetPreset(): bool
    {
        return $this->usesNamedPreset() && ! $this->isModified('widths');
    }

    /** @return array<string, mixed> */
    protected function resolvePreset(): array
    {
        if ($this->usesNamedPreset()) {
            return $this->loadPreset($this->preset);
        }

        return $this->preset;
    }

    /** @return string[] */
    protected function resolveFormats(): array
    {
        return array_map(
            fn (string $format) => $format === 'auto' ? Str::after($this->image->mime(), '/') : $format,
            $this->formats
        );
    }

    /** @return int[] */
    protected function resolveWidths(): array
    {
        $widths = array_map(
            fn (int|string $width) => $width === 'auto' ? $this->image->width() : (int) $width,
            $this->widths
        );

        sort($widths, SORT_NUMERIC);

        return $widths;
    }

    /** @param 'jpeg'|'png'|'avif'|'webp' $format */
    protected function resolveQuality(string $format): ?int
    {
        $quality = $this->modification('quality');

        if ($quality === null) {
            return null;
        }

        if ($quality !== 'auto') {
            return (int) $quality;
        }

        $quality = App::instance()->option('thumbs.quality', 90);

        return match ($format) {
            'avif' => (int) round(0.6 * $quality),
            'webp' => (int) round(0.75 * $quality),
            default => null,
        };
    }

    /** @return Collection<array<string, mixed>> */
    protected function resolveSrcsets(): Collection
    {
        $options = [];

        foreach ($this->resolveWidths() as $width) {
            $options["{$width}w"] = ['width' => $width];
        }

        $srcsets = $this->usesSrcsetPreset()
            ? $this->loadSrcsetPreset($this->preset, $options)
            : $options;

        return (new Collection($srcsets))->filter(is_array(...));
    }

    /** @return array<string, array<string, mixed>> */
    protected function resolveSizes(string $format): array
    {
        return $this->resolveSrcsetOptions($format)
            ->filter(fn (ThumbnailOptions $options) => $options->fits(
                $this->image->width(),
                $this->image->height(),
            ))
            ->toArray(fn (ThumbnailOptions $options) => $options->toArray());
    }

    /** @param 'jpeg'|'png'|'avif'|'webp' $format */
    protected function resolveThumbOptions(string $format): ThumbnailOptions
    {
        return (new ThumbnailOptions(['width' => $this->resolveFallbackWidth(), ...$this->resolvePreset()]))
            ->merge([
                'format' => $format,
                'width' => $this->modification('width'),
                'height' => $this->modification('height'),
                'quality' => $this->resolveQuality($format),
            ])
            ->ratio($this->modification('ratio'))
            ->crop($this->modification('crop'));
    }

    /**
     * @param 'jpeg'|'png'|'avif'|'webp' $format
     * @return Collection<ThumbnailOptions>
     */
    protected function resolveSrcsetOptions(string $format): Collection
    {
        return $this->resolveSrcsets()->map(function (array $options) use ($format) {
            return (new ThumbnailOptions($this->usesNamedPreset() ? $options : $this->preset))
                ->merge([
                    'format' => $format,
                    'width' => $options['width'] ?? $this->resolveFallbackWidth(),
                    'quality' => $this->resolveQuality($format),
                ])
                ->ratio($this->modification('ratio'))
                ->crop($this->modification('crop'));
        });
    }

    /** @return 'jpeg'|'png'|'avif'|'webp' */
    protected function resolveFallbackFormat(): string
    {
        return A::last($this->resolveFormats());
    }

    protected function resolveFallbackWidth(): int
    {
        return A::first($this->resolveWidths());
    }

    protected function resolveFallbackSrcset(): ?string
    {
        return $this->generateSrcset($this->resolveFallbackFormat());
    }

    /** @return 'auto'|'100vw' */
    protected function resolveFallbackSizes(?string $loading): string
    {
        return $loading === 'lazy' ? 'auto' : '100vw';
    }

    protected function optionsFromConfig(): static
    {
        $options = App::instance()->option('hksagentur.media-kit.image', []);

        $this->formats = $options['formats'] ?? ['jpeg'];
        $this->widths = $options['widths'] ?? ['auto'];

        $this->attributes = Attributes::from($options['attributes'] ?? []);

        return $this;
    }

    protected function optionsFromProps(array $options): static
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

        if (isset($options['width'])) {
            $this->width($options['width']);
        }

        if (isset($options['height'])) {
            $this->height($options['height']);
        }

        if (isset($options['ratio'])) {
            $this->ratio($options['ratio']);
        }

        if (isset($options['crop'])) {
            $this->crop($options['crop']);
        }

        if (isset($options['attributes'])) {
            $this->attributes($options['attributes']);
        }

        return $this;
    }
}
