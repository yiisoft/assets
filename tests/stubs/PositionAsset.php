<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class PositionAsset extends AssetBundle
{
    public ?string $basePath = '@root/tests/public/files';

    public ?string $baseUrl = '/files';

    public array $css = [
        'cssFile.css',
    ];

    public array $js = [
        'jsFile.js',
    ];

    public array $depends = [
        JqueryAsset::class,
    ];
}
