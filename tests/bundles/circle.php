<?php

declare(strict_types=1);

return [
    'circle' => [
        'basePath' => '@asset',
        'baseUrl' => '@assetUrl/js',
        'js' => [
            'js/jquery.js',
        ],
        'depends' => [
            'circleDepends',
        ],
        'sourcePath' => '@sourcePath',
    ],
];
