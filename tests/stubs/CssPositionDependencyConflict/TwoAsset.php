<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\CssPositionDependencyConflict;

use Yiisoft\Assets\AssetBundle;

final class TwoAsset extends AssetBundle
{
    public ?string $basePath = '@root/tests/public/files';
    public ?string $baseUrl = '/example.com';

    public array $css = [
        'cssFile.css',
    ];

    public array $js = [
        'jsFile.js',
    ];

    public array $depends = [
        OneAsset::class,
    ];
}
