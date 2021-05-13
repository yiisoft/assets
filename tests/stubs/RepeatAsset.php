<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class RepeatAsset extends AssetBundle
{
    public array $css = ['repeat/assets/repeat/main.css'];
    public array $js = ['repeat/assets/repeat/main.js'];
}
