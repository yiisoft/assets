<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class PureAsset extends AssetBundle
{
    public array $css = ['pure/main.css'];
    public array $js = ['pure/main.js'];
}
