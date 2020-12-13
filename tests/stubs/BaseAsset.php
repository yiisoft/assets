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
 * - When not assigned $basePath, and $baseUrl they can be configured globally through the AssetManager.
 *
 * ```php
 * use Yiisoft\Assets\AssetManager;
 *
 * $AssetManager = new AssetManager($aliases, $LoggerInterface);
 *
 * $AssetManager->setBasePath($basePath);
 * $AssetManager->setBaseUrl($baseUrl);
 * ```
 *
 * - The property array $publishOptions = [] is not available, since the AssetManager is not publishing anything.
 *
 * @package Assets
 */
final class BaseAsset extends AssetBundle
{
    public ?string $basePath = '@root/tests/public/basepath';

    public ?string $baseUrl = '@assetUrl';

    public array $css = [
        'css/basePath.css',
    ];

    public array $cssOptions = [
        'integrity' => 'integrity-hash',
        'crossorigin' => 'anonymous',
    ];

    public array $js = [
        'js/basePath.js',
    ];

    public array $jsOptions = [
        'integrity' => 'integrity-hash',
        'crossorigin' => 'anonymous',
    ];

    public array $jsStrings = [
        'uniqueName' => 'app1.start();',
        'app2.start();',
        'uniqueName2' => ['app3.start();', 'position' => 1], //WebView::POSITION_HEAD
    ];

    public array $jsVars = [
        'var1' => ['option1' => 'value1'],
        'var2' => ['option2' => 'value2', 'option3' => 'value3'],
        'var3' => [['option4' => 'value4'], 'position' => 3], //WebView::POSITION_END
    ];
}
