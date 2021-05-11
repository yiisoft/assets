<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

/**
 * Class BaseAsset.
 *
 * This bundle example using $basePath without $sourcePath, specifies a directory accessible from the Web that contains
 * the asset files in this package.
 *
 * Note:
 *
 * - When not assigned $basePath, and $baseUrl they can be configured globally through the AssetLoader.
 *
 * ```php
 * use Yiisoft\Assets\AssetPublisher;
 *
 * $assetLoader = (new \Yiisoft\Assets\AssetLoader($aliases))
 *     ->withBasePath($basePath)
 *     ->withBaseUrl($baseUrl)
 * ;
 * ```
 *
 * - The property array $publishOptions = [] is not available, since the AssetManager is not publishing anything.
 */
final class BaseAsset extends AssetBundle
{
    public ?string $basePath = '@root/tests/public/basepath';

    public ?string $baseUrl = '@assetUrl';

    public array $css = [
        'css/basePath.css',
        ['css/main.css', 1],
    ];

    public array $cssOptions = [
        'integrity' => 'integrity-hash',
        'crossorigin' => 'anonymous',
    ];

    public array $js = [
        'js/basePath.js',
        ['js/main.js', 1],
    ];

    public array $jsOptions = [
        'data-test' => 'one',
    ];

    public array $jsStrings = [
        'uniqueName' => 'app1.start();',
        'app2.start();',
        'uniqueName2' => ['app3.start();', 3],
        ['app4.start();', 3],
    ];

    public array $jsVars = [
        'var1' => 'value1',
        'var2' => [1, 2],
        ['var3', 'value3', 3],
    ];
}
