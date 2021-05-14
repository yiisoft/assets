<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class JsOptionsWithIntegerKeyAsset extends AssetBundle
{
    public array $jsStrings = [
        'alert(1);',
    ];

    public array $jsOptions = [
        'hello',
        'id' => 'main',
    ];
}
