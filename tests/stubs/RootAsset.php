<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class RootAsset extends AssetBundle
{
    public ?string $basePath = '@public';

    public ?string $baseUrl = '@webRoot';

    public array $js = [
        'root.js',
    ];
}
