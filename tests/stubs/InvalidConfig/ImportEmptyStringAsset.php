<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class ImportEmptyStringAsset extends AssetBundle
{
    public array $imports = [
        'vue' => '',
    ];
}
