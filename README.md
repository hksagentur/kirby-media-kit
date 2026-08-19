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
