<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class SourceAsset extends AssetBundle
{
    public ?string $basePath = '@asset';

    public ?string $baseUrl = '@assetUrl';

    public array $css = [
        'css/stub.css',
    ];

    public array $js = [
        'js/stub.js',
    ];

    public array $depends = [
        JqueryAsset::class,
    ];

    public ?string $sourcePath = '@root/tests/public/sourcepath';
}
