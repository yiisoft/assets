<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class Level3Asset extends AssetBundle
{
    public ?string $basePath = '@root/tests/public/js';

    public ?string $baseUrl = '@assetUrl/js';
}
