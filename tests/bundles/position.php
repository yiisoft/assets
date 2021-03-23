<?php

declare(strict_types=1);

return [
    'position' => [
        'basePath' => '@root/tests/public/files',
        'baseUrl' => '/files',
        'css' => [
            'cssFile.css',
        ],
        'js' => [
            'jsFile.js',
        ],
        'depends' => [
            'jquery',
        ],
    ],
];
