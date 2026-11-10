<?php

namespace Hks\MediaKit\Cms;

use Closure;
use Hks\MediaKit\Html\Attributes;
use Hks\MediaKit\Html\Sources;
use InvalidArgumentException;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Filesystem\Asset;
use Stringable;

class PendingVideo implements Stringable
{
    protected File|Asset $video;

    protected Attributes $attributes;
    protected Modifications $modifications;

    protected File|Asset|null $poster = null;

    /** @var Closure(PendingImage)|null */
    protected ?Closure $posterCallback = null;

    public function __construct(File|Asset $file, array $options = [])
    {
        if ($file->type() !== 'video') {
            throw new InvalidArgumentException('Unexpected file type');
        }

        $this->video = $file;

        $this->attributes = new Attributes();
        $this->modifications = new Modifications();

        $this->applyConfig();
        $this->applyOptions($options);
    }

    public static function for(File|Asset $video, array $options = []): static
    {
        return new static($video, $options);
    }

    public static function from(array $options): static
    {
        return static::for($options['video'], $options);
    }

    /**
     * @param Closure(PendingImage)|null $callback
     */
    public function poster(File|Asset|null $poster, ?Closure $callback = null): static
    {
        $this->poster = $poster;
        $this->posterCallback = $callback;

        return $this;
    }

    /**
     * @param string|array<string, mixed> $preset
     * @deprecated 2.2.0 Use `poster($file, fn (PendingImage $poster) => $poster->preset(...))` instead.
     */
    public function preset(string|array $preset): static
    {
        $this->modifications->set('preset', $preset);

        return $this;
    }

    /**
     * @param 'auto'|int|null $quality
     * @deprecated 2.2.0 Use `poster($file, fn (PendingImage $poster) => $poster->quality(...))` instead.
     */
    public function quality(string|int|null $quality): static
    {
        $this->modifications->set('quality', $quality);

        return $this;
    }

    /**
     * @param 'top'|'top left'|'top right'|'left'|'center'|'right'|'bottom'|'bottom left'|'bottom right'|string|bool|null $crop
     * @deprecated 2.2.0 Use `poster($file, fn (PendingImage $poster) => $poster->crop(...))` instead.
     */
    public function crop(string|bool|null $crop): static
    {
        $this->modifications->set('crop', $crop);

        return $this;
    }

    /**
     * @param 'auto'|string|float|null|array{0: int|float, 1: int|float} $ratio
     * @deprecated 2.2.0 Use `poster($file, fn (PendingImage $poster) => $poster->ratio(...))` instead.
     */
    public function ratio(string|array|float|null $ratio): static
    {
        $this->modifications->set('ratio', ! in_array($ratio, ['auto', ''], true) ? $ratio : null);

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

    public function priority(?bool $priority = true): static
    {
        $attributes = match ($priority) {
            true => ['preload' => 'auto'],
            false => ['preload' => 'metadata'],
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

    public function controls(?bool $controls = true): static
    {
        $this->attributes->set('controls', $controls);

        return $this;
    }

    public function autoplay(?bool $autoplay = true): static
    {
        $this->attributes->set('autoplay', $autoplay);

        return $this;
    }

    public function loop(?bool $loop = true): static
    {
        $this->attributes->set('loop', $loop);

        return $this;
    }

    public function muted(?bool $muted = true): static
    {
        $this->attributes->set('muted', $muted);

        return $this;
    }

    public function playsInline(?bool $playsInline = true): static
    {
        $this->attributes->set('playsinline', $playsInline);

        return $this;
    }

    /** @param 'auto'|'metadata'|'none'|null $preload */
    public function preload(?string $preload): static
    {
        $this->attributes->set('preload', $preload);

        $this->modifications->set('preload', $preload);

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

    public function generate(): Video
    {
        $poster = $this->generatePoster();

        $sources = Sources::factory([[
            'type' => $this->video->mime(),
            'src' => $this->video->url(),
        ]]);

        $attributes = $this->attributes->clone()->mergeIfMissing($poster !== null ? [
            'poster' => $poster->attributes()->get('src'),
            'width' => $poster->attributes()->get('width'),
            'height' => $poster->attributes()->get('height'),
        ] : []);

        return new Video(
            original: $this->video,
            poster: $poster,
            sources: $sources,
            attributes: $attributes,
            priority: $this->modifications->get('priority') === true,
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
        $this->attributes->set(strtolower($method), empty($arguments) ? true : $arguments[0]);

        return $this;
    }

    protected function generatePoster(): ?Image
    {
        if ($this->poster === null) {
            return null;
        }

        $image = PendingImage::for($this->poster)
            ->formats(['auto'])
            ->widths(['auto']);

        if ($this->modifications->has('priority')) {
            $image->priority($this->modifications->get('priority'));
        }

        if ($this->modifications->has('width')) {
            $image->width($this->modifications->get('width'));
        }

        if ($this->modifications->has('height')) {
            $image->height($this->modifications->get('height'));
        }

        $this->applyPosterModifications($image);
        $this->applyPosterCallback($image);

        return $image->generate();
    }

    protected function applyConfig(): void
    {
        $options = App::instance()->option('hksagentur.media-kit.video', []);

        $this->attributes->merge($options['attributes'] ?? []);

    }

    protected function applyOptions(array $options): void
    {
        if (isset($options['poster'])) {
            $this->poster($options['poster'], $options['posterCallback'] ?? null);
        }

        if (isset($options['priority'])) {
            $this->priority($options['priority']);
        }

        if (isset($options['preset'])) {
            $this->preset($options['preset']);
        }

        if (isset($options['quality'])) {
            $this->quality($options['quality']);
        }

        if (isset($options['crop'])) {
            $this->crop($options['crop']);
        }

        if (isset($options['ratio'])) {
            $this->ratio($options['ratio']);
        }

        if (isset($options['width'])) {
            $this->width($options['width']);
        }

        if (isset($options['height'])) {
            $this->height($options['height']);
        }

        if (isset($options['attributes'])) {
            $this->attributes($options['attributes']);
        }
    }

    protected function applyPosterCallback(PendingImage $image): void
    {
        if ($this->posterCallback !== null) {
            ($this->posterCallback)($image);
        }
    }

    /** @deprecated 2.2.0 Applies the poster setters deprecated together with it. */
    protected function applyPosterModifications(PendingImage $image): void
    {
        if ($this->modifications->has('preset')) {
            $image->preset($this->modifications->get('preset'));
        }

        if ($this->modifications->has('quality')) {
            $image->quality($this->modifications->get('quality'));
        }

        if ($this->modifications->has('crop')) {
            $image->crop($this->modifications->get('crop'));
        }

        if ($this->modifications->has('ratio')) {
            $image->ratio($this->modifications->get('ratio'));
        }
    }
}
