<?php

Kirby::plugin('hksagentur/media-kit', [
    'options' => [
        'image' => [
            'quality' => 80,
            'formats' => [
                'webp',
                'jpeg',
            ],
            'widths' => [
                320,
                640,
                800,
                1024,
                1280,
                1600,
                1920,
            ],
            'attributes' => [],
        ],
    ],
    'fileMethods' => require __DIR__ . '/config/methods/file.php',
]);
