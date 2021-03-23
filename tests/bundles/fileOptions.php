<?php

declare(strict_types=1);

return [
    'fileOptions' => [
        'basePath' => '@root/tests/public/media',
        'baseUrl' => '/baseUrl',
        'css' => [
            'css/default_options.css',
            ['css/tv.css', 'media' => 'tv'],
            ['css/screen_and_print.css', 'media' => 'screen, print'],
        ],
        'cssOptions' => [
            'media' => 'screen',
            'hreflang' => 'en',
        ],
        'js' => [
            'js/normal.js',
            ['js/defered.js', 'defer' => true],
        ],
        'jsOptions' => [
            'charset' => 'utf-8',
        ],
    ],
];
