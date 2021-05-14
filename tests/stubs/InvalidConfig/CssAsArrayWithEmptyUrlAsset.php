<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class CssAsArrayWithEmptyUrlAsset extends AssetBundle
{
    public array $css = [
        ['', 'id' => 'main'],
    ];
}
