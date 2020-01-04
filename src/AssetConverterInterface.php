<?php
declare(strict_types=1);

namespace Yiisoft\Assets;

/**
 * The AssetConverterInterface must be implemented by asset converter classes.
 */
interface AssetConverterInterface
{
    /**
     * Converts a given asset file into a CSS or JS file.
     *
     * @param string $asset the asset file path, relative to $basePath
     * @param string $basePath the directory the $asset is relative to.
     * @param array $optionsConverter it allows you to {@see AssetConverter::runCommand} options by AssetBundle.
     *
     * @return string the converted asset file path, relative to $basePath.
     */
    public function convert(string $asset, string $basePath, array $optionsConverter = []): string;
}
