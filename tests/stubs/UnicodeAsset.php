<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class UnicodeAsset extends AssetBundle
{
    public array $css = ['unicode/русский/main.css'];
    public array $js = ['unicode/汉语漢語/main.js'];
}
