<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class JsAsArrayWithoutUrlAsset extends AssetBundle
{
    public array $js = [
        ['id' => 'main'],
    ];
}
