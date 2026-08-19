<?php

use Hks\MediaKit\ResponsiveImage;

return [

    /**
     * Converts the current file to a responsive image tag.
     */
    'toResponsiveImage' => function (string|array|null $options = null): ResponsiveImage {
        if (is_null($options)) {
            $options = ['preset' => 'default'];
        }

        if (is_string($options)) {
            $options = ['preset' => $options];
        }

        return ResponsiveImage::from(['image' => $this, ...$options]);
    },

];
