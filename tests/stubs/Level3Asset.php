<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class Level3Asset extends AssetBundle
{
    public ?string $basePath = '@public/js';

    public ?string $baseUrl = '@web/js';
}
