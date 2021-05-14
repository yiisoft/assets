<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class JsVarWithIntegerKeyAndNotArrayValueAsset extends AssetBundle
{
    public array $jsVars = [
        42,
    ];
}
