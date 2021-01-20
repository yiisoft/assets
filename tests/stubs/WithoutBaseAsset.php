<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class WithoutBaseAsset extends AssetBundle
{
    public ?string $sourcePath = '@root/tests/public/without-base';

    public array $css = [
        'stub.css',
    ];
}
