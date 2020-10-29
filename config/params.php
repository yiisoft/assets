<?php

declare(strict_types=1);

return [
    'yiisoft/asset' => [
        'assetConverter' => [
            'command' => [
                'from' => 'sass',
                'to' => 'css',
                'command' => 'sass {options} {from} {to}'
            ],
            'forceConvert' => false
        ],
        'assetPublisher' => [
            'appendTimestamp' => false,
            'assetMap' => [],
            'basePath' => null,
            'baseUrl' => null,
            'forceCopy' => false,
            'linkAssets' => false
        ],
        'assetManager' => [
            'bundles' => [],
            'register' => []
        ]
    ]
];
