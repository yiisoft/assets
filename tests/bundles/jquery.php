<?php

declare(strict_types=1);

return [
    'jquery' => [
        'basePath' => '@root/tests/public/jquery',
        'baseUrl' => '/js',
        'js' => [
            'jquery.js',
        ],
        'depends' => [
            'level3',
        ],
    ],
];
