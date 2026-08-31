# Kirby Media Kit

Effortless responsive image and video tags for [Kirby CMS](https://getkirby.com) — modern `<picture>` markup with automatic AVIF/WebP conversion and multiple breakpoints, plus `<video>` tags with a properly generated poster, no manual thumb wrangling required.

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

### `Image`

Generates a `<picture>` element with multiple `<source>` tags covering the configured image formats and widths, falling back to a plain `<img>` tag for vector images (e.g. SVGs).

```php
<?= $page->image()->toResponsiveImage() ?>
```

Build on the fluent setters to customize it — this is the recommended way to configure a call:

```php
<?php $image = $page->image()->toResponsiveImage()
    ->widths([400, 800, 1200])
    ->formats(['webp', 'jpeg'])
    ->alt($page->image()->alt())
    ->class(['hero-image']) ?>

<?= $image ?>
```

For images further down the page, mark them lazy per image rather than as a site-wide default — the
first, above-the-fold image on a page generally shouldn't be lazy-loaded:

```php
<?= $page->image()->toResponsiveImage()->loading('lazy') ?>
```

The file method also accepts a preset name directly:

```php
<?= $page->image()->toResponsiveImage('hero') ?>
```

Or, if you'd rather set several things at once without chaining, an options array (recognizing the same keys as the setters — `preset`, `quality`, `formats`, `widths`, `width`, `height`, `ratio`, `crop`, `attributes`):

```php
<?= $page->image()->toResponsiveImage([
    'formats' => ['avif', 'webp', 'jpeg'],
    'widths' => [400, 800, 1200, 1600],
    'quality' => 80,
]) ?>
```

#### Presets

`preset('name')` loads `width`/`height`/`crop` — and anything else Kirby's own `thumb()` supports — from `thumbs.presets.*` (for the single fallback `<img>`) and `thumbs.srcsets.*` (for the responsive breakpoints). This plugin doesn't invent its own preset format, it reuses Kirby's:

```php
// site/config/config.php
return [
    'thumbs' => [
        'presets' => [
            'hero' => ['width' => 1600, 'height' => 900, 'crop' => true],
        ],
        'srcsets' => [
            'hero' => [
                '800w' => ['width' => 800, 'height' => 450, 'crop' => true],
                '1600w' => ['width' => 1600, 'height' => 900, 'crop' => true],
            ],
        ],
    ],
];
```

```php
<?= $page->image()->toResponsiveImage('hero') ?>
```

A `thumbs.presets.default`/`thumbs.srcsets.default` entry (if you define one) applies automatically to every `toResponsiveImage()` call that doesn't name a different preset — matching how Kirby's own `File::thumb()` behaves without an explicit preset.

You can override just one aspect of the preset without losing the rest — `crop()`/`width()`/`height()`/`quality()` each replace only that one setting:

```php
<?= $page->image()->toResponsiveImage('hero')->crop('top') ?>

<?= $page->image()->toResponsiveImage('hero')->width(800) ?>
```

You can also pass an inline options array instead of a preset name — same rules apply, just without the Kirby config lookup:

```php
<?= $page->image()->toResponsiveImage()->preset(['width' => 800, 'crop' => 'top']) ?>
```

#### Ratio

For a fixed aspect ratio across every generated breakpoint, `ratio()` saves you from computing `width`/`height`/`crop` by hand for each one. Kirby's `thumb()` only supports a ratio via an explicit `width` + `height` + `crop` combination — which you'd otherwise have to work out separately for every width the plugin generates. Call `ratio()` once and it computes the matching `height` automatically for each breakpoint:

```php
<?= $page->image()->toResponsiveImage()
    ->widths([400, 800, 1200])
    ->ratio('16/9') ?>
```

It accepts a `'width/height'` string (e.g. the value of a `ratio` field), a `[width, height]` array, a plain float, or `'auto'`/`null` to reset it. It defaults the crop anchor to the file's own focus point (falling back to `center`) — call `crop()` explicitly to pick a different anchor, e.g. `->ratio('16/9')->crop('top')`.

It works together with `preset()` too, overriding just the preset's `height`/`crop`:

```php
<?= $page->image()->toResponsiveImage('hero')->ratio('1/1') ?>
```

### FAQ

<details>
<summary>Where does the default quality come from if I never call <code>quality()</code>?</summary>

If neither a preset nor an explicit `quality()` call sets one, it falls back to Kirby's own `thumbs.quality` as a site-wide default — see [Configuration](#configuration).

</details>

<details>
<summary>Can a preset set its own <code>format</code>?</summary>

No — `format` always matches whichever `<source>`/`<img>` is currently being generated, and a preset can never override it. Otherwise a `<source type="image/webp">` could end up pointing at a non-WebP file.

</details>

<details>
<summary>Why did my preset's <code>width</code> win even though I called <code>widths()</code>?</summary>

`widths()` configures the list of responsive breakpoints to generate — like `formats()`, it's a site-wide/config-level setting, not a per-image preference. So a preset's own `width` always wins over it for the single fallback `<img>`. If you want to force one specific width regardless of the preset, call `width()` instead — like `quality()`/`ratio()`/`crop()`, it overrides just that one aspect, and the `<img>` tag's `width`/`height` attributes always stay in sync with whatever actually gets generated.

Each responsive breakpoint, however, keeps its own width regardless of an inline preset (otherwise every breakpoint would collapse to the same size). Calling `widths()` explicitly also overrides a *named* srcset preset (`thumbs.srcsets.*`) — it discards the preset's own breakpoints (which may be individually art-directed, e.g. different crops per width) and falls back to plain breakpoints at the widths you gave it. Without an explicit `widths()` call, the named preset's own breakpoints apply as configured.

</details>

<details>
<summary>Why did <code>crop('top')</code> give me a square image?</summary>

`crop()` is a thin passthrough to Kirby's own `thumb(['crop' => ...])` — it doesn't compute a height for you. Without a `ratio()` call or an explicit `height()`, Kirby's own crop logic defaults the height to the width, i.e. a square. This is Kirby's own long-documented `thumb()` behavior, not a plugin-specific quirk, and it applies to any crop anchor (`crop(true)`, `crop('top')`, ...), not just some of them.

If you want a specific aspect ratio while cropping, pair it with `ratio()`:

```php
<?= $page->image()->toResponsiveImage()->ratio('4/3')->crop('top') ?>
```

</details>

### `Video`

Generates a `<video>` element with the file itself as its `<source>`, plus an optional generated poster image.

```php
<?= $page->file('movie.mp4')->toResponsiveVideo() ?>
```

Kirby has no way to process video, so unlike `Image`, nothing about the video file itself is ever
transformed — the poster is the one part that *is* fully generated, and it's worth adding one on every
video: without it the browser has to guess a first frame to show, and there's no way for the plugin to
work out the video's own dimensions to prevent layout shift. `preset()`/`quality()`/`ratio()`/`crop()`/
`width()`/`height()` all configure that poster — the exact same setters `Image` has, since the poster is
just a `Image` under the hood:

```php
<?= $page->file('movie.mp4')->toResponsiveVideo()
    ->poster($page->file('poster.jpg'))
    ->ratio('16/9')
    ->crop('top') ?>
```

The poster's resolved width/height (after `ratio()`/`crop()`) become the `<video>` tag's own `width`/
`height` fallback, unless you set them explicitly yourself.

Standard `<video>` attributes are available directly:

```php
<?= $page->file('movie.mp4')->toResponsiveVideo()
    ->poster($page->file('poster.jpg'))
    ->controls()
    ->autoplay(false)
    ->muted()
    ->loop()
    ->preload('metadata') ?>
```

Any other HTML attribute without a dedicated setter (`tabindex()`, `title()`, ...) still works through
the same fluent syntax, as long as its name has no dash — attributes with one (`aria-*`, `data-*`) need
the `attributes([...])` array form instead, since there's no reliable way to tell from a method name
alone whether a dash belongs in it:

```php
<?= $page->file('movie.mp4')->toResponsiveVideo()->tabindex(0) ?>
<?= $page->file('movie.mp4')->toResponsiveVideo()->attributes(['aria-label' => 'Product demo']) ?>
```

#### Multiple formats, captions & subtitles

`toResponsiveVideo()` only ever renders the video file itself as a single `<source>` — it has no opinion
about your project's own content structure for alternate formats or `<track>` elements, since that
varies too much from project to project to guess at. If you need them, override the plugin's
`media-kit/video` snippet in your own `site/snippets/media-kit/video.php`. It receives the resolved
`Hks\MediaKit\Cms\Video` instance — `original()` gives you the real Kirby `File` to read your own
blueprint fields from, and `sources()` still gives you the plugin's own base `<source>` to render
alongside whatever you add:

```php
<?php
/**
 * @var \Hks\MediaKit\Cms\Video $video
 * @var \Hks\MediaKit\Html\Attributes $attributes
 */
$original = $video->original();
?>

<video <?= $attributes ?>>
    <?php foreach ($original->formats()->toStructure() as $format): ?>
        <source <?= attr([
            'src' => $format->file()->toFile()?->url(),
            'type' => $format->file()->toFile()?->mime(),
        ]) ?>>
    <?php endforeach ?>

    <?php foreach ($video->sources() as $source): ?>
        <?= $source ?>
    <?php endforeach ?>

    <?php foreach ($original->captions()->toStructure() as $caption): ?>
        <track <?= attr([
            'src' => $caption->file()->toFile()?->url(),
            'kind' => 'captions',
            'label' => $caption->title()->value(),
            'srclang' => $caption->language()->value(),
        ]) ?>>
    <?php endforeach ?>
</video>
```

This is exactly the tradeoff `toResponsiveImage()` never has to make — every image gets format/breakpoint
negotiation out of the box, because that only ever depends on the file itself. Extra video sources and
tracks depend on how *your* project models them in content, which the plugin can't know in advance.

## Configuration

Plugin options are read from the `hksagentur.media-kit` config key — these are the site-wide defaults for `formats`/`widths`/`attributes` (image) and `attributes` (video). The image ones are separate from `width`/`height`/`crop`/`quality`, which come from [presets](#presets) instead; the video poster's equivalents come from whatever `preset()`/`ratio()`/etc. you configure on it, same as any other `Image`:

```php
<?php

// site/config/config.php
return [
    'hksagentur.media-kit' => [
        'image' => [
            'formats' => ['avif', 'webp', 'auto'],
            'widths' => [400, 800, 1200, 1600, 2000],
            'attributes' => [
                'data-pin-nopin' => 'true',
            ],
        ],
        'video' => [
            'attributes' => [
                'playsinline' => true,
            ],
        ],
    ],
];
```

There's no plugin-level `quality` default — set Kirby's own `thumbs.quality` instead, which this plugin already respects as the fallback whenever nothing else (an explicit `quality()` call, or a preset's own `quality`) specifies one:

```php
// site/config/config.php
return [
    'thumbs' => [
        'quality' => 80,
    ],
];
```

## License

ISC License. Please see [License File](LICENSE.txt) for more information.
