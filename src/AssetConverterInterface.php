<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

/**
 * The `AssetConverterInterface` must be implemented by asset converter classes. The job of such class is to
 * convert an asset from one format to another. For example, from Sass to CSS.
 *
 * @psalm-type ConverterOptions = array<string, array{command?:string,path?:string}|null>
 */
interface AssetConverterInterface
{
    /**
     * Converts a given asset file into another format.
     *
     * @param string $asset The asset file path, relative to {@see AssetBundle::$basePath}.
     * @param string $basePath The directory the $asset is relative to.
     * @param array $optionsConverter It allows you to {@see AssetConverter::runCommand()}
     * options by {@see AssetBundle}.
     *
     * @psalm-param ConverterOptions $optionsConverter
     *
     * @return string The converted asset file path, relative to {@see AssetBundle::$basePath}.
     */
    public function convert(string $asset, string $basePath, array $optionsConverter = []): string;
}
