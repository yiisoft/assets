<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class CssAsArrayWithIntegerUrlAsset extends AssetBundle
{
    public array $css = [
        [42, 'id' => 'main'],
    ];
}
