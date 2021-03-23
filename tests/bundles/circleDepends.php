<?php

declare(strict_types=1);

return [
    'circleDepends' => [
        'basePath' => '@asset',
        'baseUrl' => '@assetUrl/js',
        'js' => [
            'js/jquery.js',
        ],
        'depends' => [
            'circle',
        ],
        'sourcePath' => '@sourcePath',
    ],
];
