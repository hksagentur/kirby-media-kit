<?php

Kirby::plugin('hksagentur/media-kit', [
    'options' => [
        'image' => [
            'formats' => [
                'webp',
                'auto',
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
        ],
    ],
    'snippets' => require __DIR__ . '/config/snippets.php',
    'fileMethods' => require __DIR__ . '/config/methods/file.php',
]);
