<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class JsVarAsArrayWithStringPositionAsset extends AssetBundle
{
    public array $jsVars = [
        ['var1', 42, 'head'],
    ];
}
