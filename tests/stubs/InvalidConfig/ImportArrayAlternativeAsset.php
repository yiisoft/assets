<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;

final class ImportArrayAlternativeAsset extends AssetBundle
{
    public bool $cdn = true;

    public array $imports = [
        'name' => [
            'some-module-url',
            'scopes' => [
                'array' => []
            ],
        ],
    ];
}
