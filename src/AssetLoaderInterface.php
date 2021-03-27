<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Yiisoft\Assets\Exception\InvalidConfigException;

/**
 * The AssetLoaderInterface must be implemented by asset loader classes.
 */
interface AssetLoaderInterface
{
    /**
     * Returns the actual URL for the specified asset.
     *
     * The actual URL is obtained by prepending either {@see AssetBundle::$baseUrl} to the given asset path.
     *
     * @param AssetBundle $bundle The asset bundle which the asset file belongs to.
     * @param string $assetPath The asset path. This should be one of the assets listed in {@see AssetBundle::$js} or
     * {@see AssetBundle::$css}.
     *
     * @throws InvalidConfigException If asset files are not found.
     *
     * @return string The actual URL for the specified asset.
     */
    public function getAssetUrl(AssetBundle $bundle, string $assetPath): string;

    /**
     * Loads asset bundle class by name.
     *
     * @param string $name The asset bundle name.
     * @param array $config The asset bundle instance configuration.
     *
     * @throws InvalidConfigException For invalid asset bundle configuration.
     *
     * @return AssetBundle The asset bundle instance.
     */
    public function loadBundle(string $name, array $config = []): AssetBundle;
}
