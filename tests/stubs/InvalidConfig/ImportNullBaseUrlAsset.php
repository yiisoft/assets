<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs\InvalidConfig;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\Tests\stubs\PureAsset;

final class ImportNullBaseUrlAsset extends AssetBundle
{
    public bool $cdn = true;

    public array $imports = [
        'name' => [
            'some-module-url',
            'scopes' => [
                PureAsset::class => 'alternative',
            ],
        ],
    ];
}
