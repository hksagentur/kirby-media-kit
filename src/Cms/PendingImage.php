<?php

namespace Hks\MediaKit\Cms;

use Hks\MediaKit\Html\Attributes;
use Hks\MediaKit\Html\Sources;
use Hks\MediaKit\Toolkit\Ratio;
use Hks\MediaKit\Toolkit\TokenList;
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
    protected File|Asset $image;

    protected Attributes $attributes;
    protected Modifications $modifications;

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

        $this->attributes = new Attributes();
        $this->modifications = new Modifications();

        $this->applyConfig();
        $this->applyOptions($options);
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
        $this->modifications->set('preset', $this->preset = $preset);

        return $this;
    }

    /** @param string[] $formats */
    public function formats(array $formats): static
    {
        $this->modifications->set('formats', $this->formats = $formats);

        return $this;
    }

    /** @param 'auto'|int|null $quality */
    public function quality(string|int|null $quality): static
    {
        $this->modifications->set('quality', $quality);

        return $this;
    }

    /** @param (int|'auto')[] $widths */
    public function widths(array $widths): static
    {
        $this->modifications->set('widths', $this->widths = $widths);

        return $this;
    }

    public function width(?int $width): static
    {
        $this->attributes->set('width', $width);

        $this->modifications->set('width', $width);

        return $this;
    }

    public function height(?int $height): static
    {
        $this->attributes->set('height', $height);

        $this->modifications->set('height', $height);

        return $this;
    }

    /** @param 'auto'|string|float|null|array{0: int|float, 1: int|float} $ratio */
    public function ratio(string|array|float|null $ratio): static
    {
        $this->modifications->set('ratio', ! in_array($ratio, ['auto', ''], true) ? $ratio : null);

        return $this;
    }

    /** @param 'top'|'top left'|'top right'|'left'|'center'|'right'|'bottom'|'bottom left'|'bottom right'|string|bool|null $crop */
    public function crop(string|bool|null $crop): static
    {
        $this->modifications->set('crop', $crop);

        return $this;
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

    public function priority(?bool $priority = true): static
    {
        $attributes = match ($priority) {
            true => [
                'fetchpriority' => 'high',
                'loading' => 'eager',
                'decoding' => 'sync',
            ],
            false => [
                'fetchpriority' => null,
                'loading' => 'lazy',
                'decoding' => 'async',
            ],
            null => [],
        };

        foreach ($attributes as $name => $value) {
            if (! $this->modifications->has($name)) {
                $this->attributes->set($name, $value);
            }
        }

        $this->modifications->set('priority', $priority);

        return $this;
    }

    /** @param 'auto'|'high'|'low'|null $priority */
    public function fetchPriority(?string $priority): static
    {
        $this->attributes->set('fetchpriority', $priority);

        $this->modifications->set('fetchpriority', $priority);

        return $this;
    }

    /** @param 'lazy'|'eager'|null $strategy */
    public function loading(?string $strategy): static
    {
        $this->attributes->set('loading', $strategy);

        $this->modifications->set('loading', $strategy);

        return $this;
    }

    /** @param 'auto'|'sync'|'async'|null $strategy */
    public function decoding(?string $strategy): static
    {
        $this->attributes->set('decoding', $strategy);

        $this->modifications->set('decoding', $strategy);

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

    public function focus(string|bool|null $point = true): static
    {
        $point = match (true) {
            $point === true => $this->image->focus()->value(),
            $point === false, $point === null => null,
            default => $point,
        };

        if ($point !== null) {
            $this->attributes->append('style', "--focus-point: {$point}");
        }

        return $this;
    }

    public function generate(): Image
    {
        if (! $this->image->isResizable()) {
            $dimensions = $this->resolveDimensions(
                $this->image->width(),
                $this->image->height()
            );

            $attributes = $this->attributes->clone()->merge($dimensions)->mergeIfMissing([
                'src' => $this->image->url(),
                'alt' => $this->image->alt(),
            ])->without('srcset', 'sizes');

            return new Image(
                original: $this->image,
                sources: new Sources(),
                attributes: $attributes,
                priority: $this->modifications->get('priority') === true,
            );
        }

        $thumbnail = $this->generateThumbnail();

        $sizes = $this->resolveSizes();
        $sources = $this->generateSources($sizes);

        $dimensions = $this->resolveDimensions(
            $thumbnail->width(),
            $thumbnail->height(),
        );

        $attributes = $this->attributes->clone()->merge([
            'sizes' => $sizes,
            ...$dimensions,
        ])->mergeIfMissing([
            'src' => $thumbnail->url(),
            'srcset' => $this->attributes->get('srcset', $this->resolveFallbackSrcset(...)),
            'alt' => $this->image->alt(),
        ]);

        return new Image(
            original: $this->image,
            sources: $sources,
            attributes: $attributes,
            priority: $this->modifications->get('priority') === true
        );
    }

    public function html(array $attributes = []): string
    {
        return $this->generate()->html($attributes);
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
        $options = $this->resolveSrcsetOptions($format)
            ->filter(fn (ThumbnailOptions $options) => $options->fits(
                $this->image->width(),
                $this->image->height(),
            ))
            ->toArray(fn (ThumbnailOptions $options) => $options->toArray());

        if ($options === []) {
            return null;
        }

        return $this->image->srcset($options);
    }

    protected function generateThumbnail(): File|FileVersion|Asset
    {
        $format = $this->resolveFallbackFormat();
        $options = $this->resolveThumbnailOptions($format);

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

    /** @return array<string, mixed> */
    protected function loadPreset(string $name): array
    {
        $options = App::instance()->option("thumbs.presets.{$name}")
            ?? App::instance()->option('thumbs.presets.default');

        return is_array($options) ? $options : [];
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
        return $this->usesNamedPreset() && ! $this->modifications->has('widths');
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

    /**
     * @return array{width: int|string|null, height: int|string|null}
     */
    protected function resolveDimensions(int $width, int $height): array
    {
        $explicitWidth = $this->attributes->get('width');
        $explicitHeight = $this->attributes->get('height');

        $ratio = $width > 0 && $height > 0 ? Ratio::from([$width, $height]) : null;

        if ($ratio && is_numeric($explicitWidth) && $explicitHeight === null) {
            $explicitHeight = $ratio->heightFor((float) $explicitWidth);
        } elseif ($ratio && is_numeric($explicitHeight) && $explicitWidth === null) {
            $explicitWidth = $ratio->widthFor((float) $explicitHeight);
        }

        return [
            'width' => $explicitWidth ?? ($width ?: null),
            'height' => $explicitHeight ?? ($height ?: null),
        ];
    }

    /** @param 'jpeg'|'png'|'avif'|'webp' $format */
    protected function resolveQuality(string $format): ?int
    {
        $quality = $this->modifications->get('quality');

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

    protected function resolveSizes(): string
    {
        return TokenList::commaSeparated($this->attributes->get('sizes') ?? 'auto')
            ->when($this->attributes->get('loading') !== 'lazy', fn (TokenList $sizes) => $sizes->without('auto'))
            ->valueOr('100vw');
    }

    /** @return Collection<array<string, mixed>> */
    protected function resolveSrcsetEntries(): Collection
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

    /**
     * @param 'jpeg'|'png'|'avif'|'webp' $format
     * @return Collection<ThumbnailOptions>
     */
    protected function resolveSrcsetOptions(string $format): Collection
    {
        return $this->resolveSrcsetEntries()->map(function (array $options) use ($format) {
            return (new ThumbnailOptions($this->usesNamedPreset() ? $options : $this->preset))
                ->merge([
                    'format' => $format,
                    'width' => $options['width'] ?? $this->resolveFallbackWidth(),
                    'quality' => $this->resolveQuality($format),
                ])
                ->ratio($this->modifications->get('ratio'))
                ->crop($this->modifications->get('crop'));
        });
    }

    /** @param 'jpeg'|'png'|'avif'|'webp' $format */
    protected function resolveThumbnailOptions(string $format): ThumbnailOptions
    {
        return (new ThumbnailOptions(['width' => $this->resolveFallbackWidth(), ...$this->resolvePreset()]))
            ->merge([
                'format' => $format,
                'width' => $this->modifications->get('width'),
                'height' => $this->modifications->get('height'),
                'quality' => $this->resolveQuality($format),
            ])
            ->ratio($this->modifications->get('ratio'))
            ->crop($this->modifications->get('crop'));
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

    protected function applyConfig(): void
    {
        $options = App::instance()->option('hksagentur.media-kit.image', []);

        $this->formats = $options['formats'] ?? ['jpeg'];
        $this->widths = $options['widths'] ?? ['auto'];

        $this->attributes->merge($options['attributes'] ?? []);
    }

    protected function applyOptions(array $options): void
    {
        if (isset($options['preset'])) {
            $this->preset($options['preset']);
        }

        if (isset($options['formats'])) {
            $this->formats($options['formats']);
        }

        if (isset($options['quality'])) {
            $this->quality($options['quality']);
        }

        if (isset($options['priority'])) {
            $this->priority($options['priority']);
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
    }
}
