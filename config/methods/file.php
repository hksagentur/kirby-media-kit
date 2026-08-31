<?php

use Hks\MediaKit\Cms\PendingImage;
use Hks\MediaKit\Cms\PendingVideo;

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

    /**
     * Converts the current file to a responsive video tag.
     */
    'toResponsiveVideo' => function (array $options = []): PendingVideo {
        return PendingVideo::from(['video' => $this, ...$options]);
    },

];
