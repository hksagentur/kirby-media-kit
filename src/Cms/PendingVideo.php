<?php

namespace Hks\MediaKit\Cms;

use Hks\MediaKit\Html\Attributes;
use Hks\MediaKit\Html\Sources;
use InvalidArgumentException;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Filesystem\Asset;
use Stringable;

class PendingVideo implements Stringable
{
    use HasModifications;

    protected File|Asset $video;
    protected Attributes $attributes;

    protected File|Asset|null $poster = null;

    public function __construct(File|Asset $file, array $options = [])
    {
        if ($file->type() !== 'video') {
            throw new InvalidArgumentException('Unexpected file type');
        }

        $this->video = $file;

        $this->optionsFromConfig();
        $this->optionsFromProps($options);
    }

    public static function for(File|Asset $video, array $options = []): static
    {
        return new static($video, $options);
    }

    public static function from(array $options): static
    {
        return static::for($options['video'], $options);
    }

    public function poster(File|Asset|null $poster): static
    {
        $this->poster = $poster;

        return $this;
    }

    /** @param string|array<string, mixed> $preset */
    public function preset(string|array $preset): static
    {
        return $this->modify('preset', $preset);
    }

    /** @param 'auto'|int|null $quality */
    public function quality(string|int|null $quality): static
    {
        return $this->modify('quality', $quality);
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

        return $this;
    }

    /** @param 'anonymous'|'use-credentials'|null $crossOrigin */
    public function crossOrigin(?string $crossOrigin): static
    {
        $this->attributes->set('crossorigin', $crossOrigin);

        return $this;
    }

    public function draggable(?bool $draggable = true): static
    {
        $this->attributes->set('draggable', $draggable);

        return $this;
    }

    public function generate(): Video
    {
        $poster = $this->generatePoster();

        $sources = Sources::factory([[
            'type' => $this->video->mime(),
            'src' => $this->video->url(),
        ]]);

        return new Video($this->video, $poster, $sources, $this->attributes->clone()->mergeIfMissing(
            $poster === null ? [] : [
                'poster' => $poster->attributes()->get('src'),
                'width' => $poster->attributes()->get('width'),
                'height' => $poster->attributes()->get('height'),
            ]
        ));
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

    protected function generatePoster(): ?Image
    {
        if ($this->poster === null) {
            return null;
        }

        $image = PendingImage::for($this->poster)
            ->widths(['auto'])
            ->formats(['auto']);

        if ($this->isModified('preset')) {
            $image->preset($this->modification('preset'));
        }

        if ($this->isModified('quality')) {
            $image->quality($this->modification('quality'));
        }

        if ($this->isModified('ratio')) {
            $image->ratio($this->modification('ratio'));
        }

        if ($this->isModified('crop')) {
            $image->crop($this->modification('crop'));
        }

        if ($this->isModified('width')) {
            $image->width($this->modification('width'));
        }

        if ($this->isModified('height')) {
            $image->height($this->modification('height'));
        }

        return $image->generate();
    }

    protected function optionsFromConfig(): static
    {
        $options = App::instance()->option('hksagentur.media-kit.video', []);

        $this->attributes = Attributes::from($options['attributes'] ?? []);

        return $this;
    }

    protected function optionsFromProps(array $options): static
    {
        if (isset($options['poster'])) {
            $this->poster($options['poster']);
        }

        if (isset($options['preset'])) {
            $this->preset($options['preset']);
        }

        if (isset($options['quality'])) {
            $this->quality($options['quality']);
        }

        if (isset($options['ratio'])) {
            $this->ratio($options['ratio']);
        }

        if (isset($options['crop'])) {
            $this->crop($options['crop']);
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

        return $this;
    }
}
