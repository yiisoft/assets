<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class CdnAsset extends AssetBundle
{
    public bool $cdn = true;

    public array $js = [
        'https://example.com/script.js',
    ];
}
