<?php

declare(strict_types=1);

use Yiisoft\Assets\AssetLoaderInterface;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Assets\Debug\AssetCollector;
use Yiisoft\Assets\Debug\AssetLoaderInterfaceProxy;

return [
    'yiisoft/assets' => [
        'assetConverter' => [
            'commands' => [
                'scss' => ['css', '@npm/.bin/sass {options} {from} {to}'],
            ],
            'forceConvert' => false,
        ],
        'assetLoader' => [
            'appendTimestamp' => false,
            'assetMap' => [],
            'basePath' => null,
            'baseUrl' => null,
        ],
        'assetPublisher' => [
            'forceCopy' => false,
            'linkAssets' => false,
        ],
        'assetManager' => [
            'allowedBundleNames' => [],
            'customizedBundles' => [],
            'register' => [],
            'publisher' => AssetPublisherInterface::class,
        ],
    ],

    'yiisoft/yii-debug' => [
        'collectors.web' => [
            AssetCollector::class,
        ],
        'trackedServices' => [
            AssetLoaderInterface::class => [AssetLoaderInterfaceProxy::class, AssetCollector::class],
        ],
        'ignoredRequests' => [
            '/assets/**',
        ],
    ],
];
