<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class ImportEmptyKeyAsset extends AssetBundle
{
    public array $imports = [
        '' => 'module.js'
    ];
}
