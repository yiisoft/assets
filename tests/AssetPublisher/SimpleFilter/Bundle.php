<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\AssetPublisher\SimpleFilter;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Files\PathMatcher\PathMatcher;

final class Bundle extends AssetBundle
{
    public ?string $basePath = '@asset';

    public ?string $baseUrl = '@assetUrl';

    public array $css = [
        'css/bootstrap.css',
    ];

    public array $js = [
        'js/bootstrap.js',
    ];

    public ?string $sourcePath = __DIR__ . '/source';

    public function __construct()
    {
        $pathMatcher = new PathMatcher();
        $this->publishOptions = [
            'filter' => $pathMatcher->only('**/css/bootstrap.css', '**/js/bootstrap.js'),
        ];
    }
}
