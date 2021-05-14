<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class JsVarAsArrayWithIntegerNameAsset extends AssetBundle
{
    public array $jsVars = [
        [3],
    ];
}
