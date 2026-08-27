<?php

use Hks\MediaKit\PendingResponsiveImage;

return [

    /**
     * Converts the current file to a responsive image tag.
     */
    'toResponsiveImage' => function (string|array|null $options = null): PendingResponsiveImage {
        if (is_null($options)) {
            $options = ['preset' => 'default'];
        }

        if (is_string($options)) {
            $options = ['preset' => $options];
        }

        return PendingResponsiveImage::from(['image' => $this, ...$options]);
    },

];
