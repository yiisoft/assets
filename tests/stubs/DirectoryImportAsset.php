<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class DirectoryImportAsset extends AssetBundle
{
    public ?string $basePath = '@root/tests/public/basepath';

    public ?string $baseUrl = '@assetUrl';

    public array $imports = [
        'base/' => 'js/',
    ];
}
