<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Yiisoft\Assets\Exception\InvalidConfigException;

/**
 * The `AssetLoaderInterface` must be implemented by asset loader classes. The job of such class is executing the loading
 * of the assets from {@see AssetBundle::$basePath} to {@see AssetBundle::$baseUrl}.
 */
interface AssetLoaderInterface
{
    /**
     * Returns the actual URL for the specified asset.
     *
     * The actual URL is obtained by prepending {@see AssetBundle::$baseUrl} to the given asset path.
     *
     * @param AssetBundle $bundle The asset bundle which the asset file belongs to.
     * @param string $assetPath The asset path. See {@see AssetBundle::$js} and {@see AssetBundle::$css}.
     *
     * @throws InvalidConfigException If asset files are not found.
     *
     * @return string The actual URL for the specified asset.
     */
    public function getAssetUrl(AssetBundle $bundle, string $assetPath): string;

    /**
     * Loads an asset bundle class by name or creates an instance of the asset bundle class, if class name not exists.
     *
     * @param string $name The asset bundle name.
     * @param array $config The asset bundle instance configuration.
     *
     * @psalm-param array<string, mixed> $config
     *
     * @throws InvalidConfigException For invalid asset bundle configuration.
     *
     * @return AssetBundle The asset bundle instance.
     */
    public function loadBundle(string $name, array $config = []): AssetBundle;
}
