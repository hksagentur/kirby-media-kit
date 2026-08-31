<?php

use Hks\MediaKit\Cms\PendingImage;

return [

    /**
     * Converts the current file to a responsive image tag.
     */
    'toResponsiveImage' => function (string|array|null $options = null): PendingImage {
        if (is_null($options)) {
            $options = ['preset' => 'default'];
        }

        if (is_string($options)) {
            $options = ['preset' => $options];
        }

        return PendingImage::from(['image' => $this, ...$options]);
    },

];
