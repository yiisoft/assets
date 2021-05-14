<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class JsAsArrayWithEmptyUrlAsset extends AssetBundle
{
    public array $js = [
        ['', 'id' => 'main'],
    ];
}
