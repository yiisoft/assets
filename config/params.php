<?php

declare(strict_types=1);

return [
    'aliases' => [
        '@root' => dirname(__DIR__, 1),
        '@asset' => '@root/tests/public/assets',
        '@assetUrl'  => '/baseUrl',
        '@converter' => '@root/tests/public/assetconverter',
        '@npm' => '@root/node_modules',
        '@testSourcePath' => '@root/tests/public/assetsources'
    ],

    'yiisoft/asset' => [
        'assetConverter' => [
            'command' => [
                'from' => '',
                'to' => '',
                'command' => ''
            ],
            'forceConvert' => false
        ],
        'assetPublisher' => [
            'appendTimestamp' => false,
            'assetMap' => [],
            'basePath' => '',
            'baseUrl' => '',
            'forceCopy' => false,
            'linkAssets' => false,
        ],
        'assetManager' => [
            'bundles' => [
            ],
            'register' => [
            ],
        ],
    ],
];
