<?php
/**
 * @var \Hks\MediaKit\Html\Attributes $attributes
 * @var \Hks\MediaKit\Cms\Video $video
 */
?>

<video <?= $attributes ?>>
    <?php foreach ($video->sources() as $source): ?>
        <?= $source ?>
    <?php endforeach ?>
</video>
