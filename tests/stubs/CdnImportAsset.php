<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class CdnImportAsset extends AssetBundle
{
    public bool $cdn = true;

    public array $imports = [
        'vue' => 'https://cdn.jsdelivr.net/npm/vue@3.5.32/+esm',
    ];
}
