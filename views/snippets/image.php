<?php
/**
 * @var \Hks\MediaKit\Html\Attributes $attributes
 * @var \Hks\MediaKit\Html\Sources $sources
 */
?>

<?php if ($sources->isNotEmpty()): ?>
    <picture>
        <?php foreach ($sources as $source): ?>
            <source <?= attr([
                'type' => $source->type(),
                'srcset' => $source->srcset(),
                'sizes' => $source->sizes(),
            ]) ?>>
        <?php endforeach ?>

        <img <?= $attributes ?>>
    </picture>
<?php else: ?>
    <img <?= $attributes ?>>
<?php endif ?>
