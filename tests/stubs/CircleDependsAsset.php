<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

class CircleDependsAsset extends AssetBundle
{
    public ?string $basePath = '@public/assets';

    public ?string $baseUrl = '@web/js';

    public array $js = [
        'js/jquery.js',
    ];

    public array $depends = [
        CircleAsset::class,
    ];

    public ?string $sourcePath = '@public/sourcepath';
}
