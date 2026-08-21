# Kirby Media Kit

Effortless responsive image tags for [Kirby CMS](https://getkirby.com) — modern `<picture>` markup with automatic AVIF/WebP conversion and multiple breakpoints, no manual thumb wrangling required.

## Requirements

Kirby CMS (`>=5.5`)  
PHP (`>= 8.2`)

## Installation

### Composer

```sh
composer require hksagentur/kirby-media-kit
```

### Download

Download the project archive and copy the files to the plugin directory of your kirby installation. By default this directory is located at `/site/plugins`.

## Usage

### `ResponsiveImage`

Generates a `<picture>` element with multiple `<source>` tags covering the configured image formats and widths, falling back to a plain `<img>` tag for vector images (e.g. SVGs).

```php
<?= $page->image()->toResponsiveImage() ?>
```

The file method accepts either a preset name or an options array:

```php
<?= $page->image()->toResponsiveImage('hero') ?>

<?= $page->image()->toResponsiveImage([
    'formats' => ['avif', 'webp', 'jpeg'],
    'widths' => [400, 800, 1200, 1600],
    'quality' => 80,
]) ?>
```

You can also build on the fluent setters directly:

```php
<?php $image = $page->image()->toResponsiveImage()
    ->widths([400, 800, 1200])
    ->formats(['webp', 'jpeg'])
    ->alt($page->image()->alt())
    ->classList(['hero-image']) ?>

<?= $image ?>
```

Call `ratio()` to crop every generated width to a fixed aspect ratio, without having to define a named `thumbs.presets.*` entry. It accepts a `'width/height'` string (e.g. the value of a `ratio` field), a `[width, height]` array, a plain float, or `'auto'`/`null` to reset it:

```php
<?= $page->image()->toResponsiveImage()
    ->ratio('16/9')
    ->widths([400, 800, 1200]) ?>
```

`ratio()` only applies when no named preset is used — once `preset()` is set, the preset's own `thumbs.presets.*`/`thumbs.srcsets.*` configuration takes over completely.

`ratio()` defaults the crop anchor to the file's own focus point (falling back to `center`). Call `crop()` explicitly to pick a different anchor, e.g. `->ratio('16/9')->crop('top')`. `crop()` accepts any of Kirby's own crop values (`'top'`, `'bottom left'`, `true`, `false`, …) and works independently of `ratio()` too.

## Configuration

Plugin options are read from the `hksagentur.media-kit` config key:

```php
<?php

// site/config/config.php
return [
    'hksagentur.media-kit' => [
        'image' => [
            'formats' => ['webp', 'jpeg'],
            'widths' => [400, 800, 1200, 1600, 2000],
            'quality' => 80,
            'attributes' => [
                'loading' => 'lazy',
                'decoding' => 'async',
            ],
        ],
    ],
];
```

## License

ISC License. Please see [License File](LICENSE.txt) for more information.
