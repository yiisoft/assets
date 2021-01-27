<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class CdnWithBaseUrlAsset extends AssetBundle
{
    public bool $cdn = true;
    public ?string $baseUrl = 'https://example.com/base';

    public array $js = [
        'script.js',
    ];
}
