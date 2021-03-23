<?php

declare(strict_types=1);

/**
 * Example bundle with "basePath" wrong.
 *
 * When using it, an exception of `Yiisoft\Assets\Exception\InvalidConfigException` will be thrown.
 */
return [
    'baseWrong' => [
        'basePath' => '/wrongbasepath',
        'baseUrl' => '/baseUrl',
        'css' => [
            'css/basePath.css',
        ],
        'cssOptions' => [
            'integrity' => 'integrity-hash',
            'crossorigin' => 'anonymous',
        ],
        'js' => [
            'js/basePath.js',
        ],
        'jsOptions' => [
            'integrity' => 'integrity-hash',
            'crossorigin' => 'anonymous',
        ],
    ],
];
