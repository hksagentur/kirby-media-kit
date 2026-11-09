<?php
/**
 * @var \Hks\MediaKit\Html\Attributes $attributes
 * @var \Hks\MediaKit\Cms\Video $video
 */
?>

<video <?= $attributes->style($video->poster()?->attributes()->get('style')) ?>>
    <?php foreach ($video->sources() as $source): ?>
        <?= $source ?>
    <?php endforeach ?>
</video>
