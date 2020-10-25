<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Factory\Definitions\Reference;

/* @var array $params */

return [
    AssetConverterInterface::class => [
        '__class' => AssetConverter::class,
        '__construct()' => [
            Reference::to(Aliases::class),
            Reference::to(LoggerInterface::class)
        ],
        'setCommand()' => [
            $params['yiisoft/asset']['assetConverter']['command']['from'],
            $params['yiisoft/asset']['assetConverter']['command']['to'],
            $params['yiisoft/asset']['assetConverter']['command']['command']
        ],
        'setForceConvert()' => [$params['yiisoft/asset']['assetConverter']['forceConvert']]
    ],

    AssetPublisherInterface::class => [
        '__class' => AssetPublisher::class,
        '__construct()' => [Reference::to(Aliases::class)],
        'setAppendTimestamp()' => $params['yiisoft/asset']['assetPublisher']['appendTimestamp'],
        'setAssetMap()' => $params['yiisoft/asset']['assetPublisher']['assetMap'],
        'setBasePath()' => $params['yiisoft/asset']['assetPublisher']['basePath'],
        'setBaseUrl()' => $params['yiisoft/asset']['assetPublisher']['baseUrl'],
        'setForceCopy()' => $params['yiisoft/asset']['assetPublisher']['forceCopy'],
        'setLinkAssets()' => $params['yiisoft/asset']['assetPublisher']['linkAssets']

    ],

    AssetManager::class => [
        '__class' => AssetManager::class,
        '__construct()' => [Reference::to(LoggerInterface::class)],
        'setConverter()' => [Reference::to(AssetConverterInterface::class)],
        'setPublisher()' => [Reference::to(AssetPublisherInterface::class)],
        'setBundles()' => [$params['yiisoft/asset']['assetManager']['bundles']],
        'register()' => [$params['yiisoft/asset']['assetManager']['register']]
    ]
];
