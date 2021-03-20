<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Yiisoft\Assets\Exception\InvalidConfigException;

/**
 * The AssetPublisherInterface must be implemented by asset publisher classes.
 */
interface AssetPublisherInterface
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
     * @throws InvalidConfigException
     *
     * @return string The actual URL for the specified asset.
     */
    public function getAssetUrl(AssetBundle $bundle, string $assetPath): string;

    /**
     * Loads asset bundle class by name.
     *
     * @param string $name The bundle name.
     * @param array $config The bundle object configuration.
     *
     * @throws InvalidConfigException
     *
     * @return AssetBundle
     */
    public function loadBundle(string $name, array $config = []): AssetBundle;

    /**
     * Publishes a file or a directory.
     *
     * This method will copy the specified file or directory to {@see AssetBundle::$basePath}
     * so that it can be accessed via the Web server.
     *
     * If the asset is a file, its file modification time will be checked to avoid unnecessary file copying.
     *
     * If the asset is a directory, all files and subdirectories under it will be published recursively. Note, in case
     * $forceCopy is false the method only checks the existence of the target directory to avoid repetitive copying
     * (which is very expensive).
     *
     * By default, when publishing a directory, subdirectories and files whose name starts with a dot "." will NOT be
     * published.
     *
     * Note: On rare scenario, a race condition can develop that will lead to a  one-time-manifestation of a
     * non-critical problem in the creation of the directory that holds the published assets. This problem can be
     * avoided altogether by 'requesting' in advance all the resources that are supposed to trigger a 'publish()' call,
     * and doing that in the application deployment phase, before system goes live. See more in the following
     * discussion: http://code.google.com/p/yii/issues/detail?id=2579
     *
     * @param AssetBundle $bundle The asset (file or directory) to be read.
     *
     * - only: array, list of patterns that the file paths should match if they want to be copied.
     *
     * @throws InvalidConfigException If the asset to be published does not exist.
     *
     * @return array The path (directory or file path) and the URL that the asset is published as.
     */
    public function publish(AssetBundle $bundle): array;
}
