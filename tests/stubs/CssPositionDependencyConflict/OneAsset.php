<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\CssPositionDependencyConflict;

use Yiisoft\Assets\AssetBundle;

final class OneAsset extends AssetBundle
{
    public ?string $basePath = '@root/tests/public/pure';
    public ?string $baseUrl = '/example.com';

    public array $css = [
        'main.css',
    ];

    public array $js = [
        'main.js',
    ];
}
