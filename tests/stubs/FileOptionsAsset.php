<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;

final class FileOptionsAsset extends AssetBundle
{
    public ?string $basePath = '@public/media';

    public ?string $baseUrl = '/baseUrl';

    public array $css = [
        'css/default_options.css',
        ['css/tv.css', 'media' => 'tv'],
        ['css/screen_and_print.css', 'media' => 'screen, print'],
    ];

    public array $cssOptions = ['media' => 'screen', 'hreflang' => 'en'];

    public array $js = [
        'js/normal.js',
        ['js/defered.js', 'defer' => true],
    ];

    public array $jsOptions = ['charset' => 'utf-8'];
}
