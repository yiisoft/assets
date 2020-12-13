<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\stubs;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\Exception\InvalidConfigException;

/**
 * Class BaseWrongAsset.
 *
 * Example class with $basePath wrong.
 *
 * @package Assets
 *
 * @throws InvalidConfigException
 */
final class BaseWrongAsset extends AssetBundle
{
    public ?string $basePath = '/wrongbasepath';

    public ?string $baseUrl = '/baseUrl';

    public array $css = [
        'css/basePath.css',
    ];

    public array $cssOptions = [
        'integrity' => 'integrity-hash',
        'crossorigin' => 'anonymous',
    ];

    public array $js = [
        'js/basePath.js',
    ];

    public array $jsOptions = [
        'integrity' => 'integrity-hash',
        'crossorigin' => 'anonymous',
    ];
}
