<?php
/**
 * @var \Hks\MediaKit\Html\Attributes $attributes
 * @var \Hks\MediaKit\Cms\Image $image
 */
?>

<?php if ($image->hasSources()): ?>
    <picture>
        <?php foreach ($image->sources() as $source): ?>
            <?= $source ?>
        <?php endforeach ?>

        <img <?= $attributes ?>>
    </picture>
<?php else: ?>
    <img <?= $attributes ?>>
<?php endif ?>
