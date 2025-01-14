<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Yiisoft\Assets\Exception\InvalidConfigException;

/**
 * The `AssetPublisherInterface` must be implemented by asset publisher classes.
 *
 * @psalm-type PublishedBundle = array{0:non-empty-string,1:non-empty-string}
 */
interface AssetPublisherInterface
{
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
     * Note: On rare scenario, a race condition can develop that will lead to a one-time-manifestation of a
     * non-critical problem in the creation of the directory that holds the published assets. This problem can be
     * avoided altogether by 'requesting' in advance all the resources that are supposed to trigger a 'publish()' call,
     * and doing that in the application deployment phase, before system goes live. See more in the following
     * discussion: {@link https://code.google.com/archive/p/yii/issues/2579}
     *
     * @param AssetBundle $bundle The asset (file or directory) to be read.
     *
     * - only: array, list of patterns that the file paths should match if they want to be copied.
     *
     * @throws InvalidConfigException If the asset or the asset file paths to be published does not exist.
     *
     * @return array The path (directory or file path) and the URL that the asset is published as.
     *
     * @psalm-return PublishedBundle
     */
    public function publish(AssetBundle $bundle): array;

    /**
     * Returns the published path of a file path.
     *
     * This method does not perform any publishing. It merely tells you if the file or directory is published, where it
     * will go.
     *
     * @param string $sourcePath The directory or file path being published.
     *
     * @return string|null The string the published file path. Null if the file or directory does not exist.
     */
    public function getPublishedPath(string $sourcePath): ?string;

    /**
     * Returns the URL of a published file path.
     *
     * This method does not perform any publishing. It merely tells you if the file path is published,
     * what the URL will be to access it.
     *
     * @param string $sourcePath The directory or file path being published.
     *
     * @return string|null The string the published URL for the file or directory. Null if the file or directory does
     * not exist.
     */
    public function getPublishedUrl(string $sourcePath): ?string;
}
