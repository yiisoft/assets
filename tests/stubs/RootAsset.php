<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class RootAsset extends AssetBundle
{
    public ?string $basePath = '@root/tests/public';

    public ?string $baseUrl = '@assetUrl';

    public array $js = [
        'root.js',
    ];
}
