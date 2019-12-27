<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class SimpleAsset extends AssetBundle
{
    public ?string $basePath = '@public/jquery';

    public ?string $baseUrl = '/js';

    public array $js = [
        'jquery.js',
    ];
}
