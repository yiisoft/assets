<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class WebpackAsset extends AssetBundle
{
    public ?string $basePath = '@asset';

    public ?string $baseUrl = '@assetUrl';

    public array $css = [
        'webpack/stub.css',
        'css/stub.css',
    ];

    public array $js = [
        ['webpack/stub.js', 'defer' => true],
        'js/stub.js',
    ];

    public array $depends = [
        ExportAsset::class,
    ];

    public ?string $sourcePath = '@sourcePath';
}
