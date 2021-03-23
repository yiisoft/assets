<?php

declare(strict_types=1);

/**
 * This bundle example using "basePath" without "sourcePath", specifies a directory accessible
 * from the Web that contains the asset files in this bundle.
 *
 * Note:
 *
 * - When not assigned "basePath", and "baseUrl" they can be configured globally through the AssetPublisher.
 *
 * ```php
 * use Yiisoft\Assets\AssetPublisher;
 *
 * $AssetPublisher = new AssetPublisher($aliases);
 *
 * $AssetPublisher->setBasePath($basePath);
 * $AssetPublisher->setBaseUrl($baseUrl);
 * ```
 *
 * - The array key "publishOptions" is not available, since the AssetManager is not publishing anything.
 */
return [
    'base' => [
        'basePath' => '@root/tests/public/basepath',
        'baseUrl' => '@assetUrl',
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
        'jsStrings' => [
            'uniqueName' => 'app1.start();',
            'app2.start();',
            'uniqueName2' => ['app3.start();', 'position' => 1], // WebView::POSITION_HEAD
        ],
        'jsVar' => [
            'var1' => ['option1' => 'value1'],
            'var2' => ['option2' => 'value2', 'option3' => 'value3'],
            'var3' => [['option4' => 'value4'], 'position' => 3], // WebView::POSITION_END
        ],
    ],
];
