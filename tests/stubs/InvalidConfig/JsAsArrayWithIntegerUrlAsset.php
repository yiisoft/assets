<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class JsAsArrayWithIntegerUrlAsset extends AssetBundle
{
    public array $js = [
        [42, 'id' => 'main'],
    ];
}
