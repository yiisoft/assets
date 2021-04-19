<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class ExportAsset extends AssetBundle
{
    public ?string $basePath = '@asset';

    public ?string $baseUrl = '@assetUrl';

    public array $css = [
        'export/stub.css',
    ];

    public array $js = [
        'export/stub.js',
    ];

    public array $export = [
        'export/yii-logo.png',
        'export/stub.css',
    ];

    public array $depends = [
        SourceAsset::class,
    ];

    public ?string $sourcePath = '@sourcePath';
}
