<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Exception;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Files\FileHelper;

use function array_merge;
use function crc32;
use function dirname;
use function file_exists;
use function is_callable;
use function is_dir;
use function is_file;
use function sprintf;
use function symlink;

/**
 * AssetPublisher is responsible for executing the publication of the assets
 * from {@see AssetBundle::$sourcePath} to {@see AssetBundle::$basePath}.
 */
final class AssetPublisher implements AssetPublisherInterface
{
    private Aliases $aliases;

    /**
     * @var int The permission to be set for newly generated asset directories. This value will be used by PHP chmod()
     * function. No umask will be applied. Defaults to 0775, meaning the directory is read-writable by owner
     * and group, but read-only for other users.
     */
    private int $dirMode = 0775;

    /**
     * @var int The permission to be set for newly published asset files. This value will be used by PHP chmod()
     * function. No umask will be applied. If not set, the permission will be determined by the current
     * environment.
     */
    private int $fileMode = 0755;

    /**
     * @var bool Whether the directory being published should be copied even if it is found in the target directory.
     * This option is used only when publishing a directory. You may want to set this to be `true` during the
     * development stage to make sure the published directory is always up-to-date. Do not set this to true
     * on production servers as it will significantly degrade the performance.
     */
    private bool $forceCopy = false;

    /**
     * @var callable|null A callback that will be called to produce hash for asset directory generation. The signature
     * of the callback should be as follows:
     *
     * ```
     * function ($path)
     * ```
     *
     * Where `$path` is the asset path. Note that the `$path` can be either directory where the asset files reside or a
     * single file. For a CSS file that uses relative path in `url()`, the hash implementation should use the directory
     * path of the file instead of the file path to include the relative asset files in the copying.
     *
     * If this is not set, the asset manager will use the default CRC32 and filemtime in the `hash` method.
     *
     * Example of an implementation using MD4 hash:
     *
     * ```php
     * function ($path) {
     *     return hash('md4', $path);
     * }
     * ```
     */
    private $hashCallback = null;

    /**
     * @var bool Whether to use symbolic link to publish asset files. Defaults to false, meaning asset files are copied
     * to {@see basePath}. Using symbolic links has the benefit that the published assets will always be consistent
     * with the source assets and there is no copy operation required. This is especially useful during development.
     *
     * However, there are special requirements for hosting environments in order to use symbolic links. In particular,
     * symbolic links are supported only on Linux/Unix, and Windows Vista/2008 or greater.
     *
     * Moreover, some Web servers need to be properly configured so that the linked assets are accessible to Web users.
     * For example, for Apache Web server, the following configuration directive should be added for the Web folder:
     *
     * ```apache
     * Options FollowSymLinks
     * ```
     */
    private bool $linkAssets = false;

    /**
     * @var array Contain published {@see AssetsBundle}.
     */
    private array $published = [];

    public function __construct(Aliases $aliases)
    {
        $this->aliases = $aliases;
    }

    public function publish(AssetBundle $bundle): array
    {
        if (empty($bundle->sourcePath)) {
            throw new InvalidConfigException(
                'The sourcePath must be defined in AssetBundle property public ?string $sourcePath = $path.',
            );
        }

        if (isset($this->published[$bundle->sourcePath])) {
            return $this->published[$bundle->sourcePath];
        }

        if (empty($bundle->basePath)) {
            throw new InvalidConfigException(
                'The basePath must be defined in AssetBundle property public ?string $basePath = $path.',
            );
        }

        if ($bundle->baseUrl === null) {
            throw new InvalidConfigException(
                'The baseUrl must be defined in AssetBundle property public ?string $baseUrl = $path.',
            );
        }

        if (!file_exists($this->aliases->get($bundle->sourcePath))) {
            throw new InvalidConfigException("The sourcePath to be published does not exist: {$bundle->sourcePath}");
        }

        return $this->published[$bundle->sourcePath] = $this->publishBundleDirectory($bundle);
    }

    /**
     * Return config linkAssets.
     *
     * @return bool
     */
    public function getLinkAssets(): bool
    {
        return $this->linkAssets;
    }

    /**
     * Returns the published path of a file path.
     *
     * This method does not perform any publishing. It merely tells you if the file or directory is published, where it
     * will go.
     *
     * @param string $sourcePath The directory or file path being published.
     *
     * @return string|null The string the published file path. Null if the file or directory does not exist
     */
    public function getPublishedPath(string $sourcePath): ?string
    {
        if (isset($this->published[$sourcePath])) {
            return $this->published[$sourcePath][0];
        }

        return null;
    }

    /**
     * Returns the URL of a published file path.
     *
     * This method does not perform any publishing. It merely tells you if the file path is published,
     * what the URL will be to access it.
     *
     * @param string $sourcePath The directory or file path being published.
     *
     * @return string|null The string the published URL for the file or directory.
     * Null if the file or directory does not exist.
     */
    public function getPublishedUrl(string $sourcePath): ?string
    {
        if (isset($this->published[$sourcePath])) {
            return $this->published[$sourcePath][1];
        }

        return null;
    }

    /**
     * The permission to be set for newly generated asset directories.
     *
     * @param int $value
     *
     * {@see dirMode}
     */
    public function setDirMode(int $value): void
    {
        $this->dirMode = $value;
    }

    /**
     * The permission to be set for newly published asset files.
     *
     * @param int $value
     *
     * {@see fileMode}
     */
    public function setFileMode(int $value): void
    {
        $this->fileMode = $value;
    }

    /**
     * Whether the directory being published should be copied even if it is found in the target directory.
     *
     * @param bool $value
     *
     * {@see forceCopy}
     */
    public function setForceCopy(bool $value): void
    {
        $this->forceCopy = $value;
    }

    /**
     * A callback that will be called to produce hash for asset directory generation.
     *
     * @param callable $value
     *
     * {@see hashCallback}
     */
    public function setHashCallback(callable $value): void
    {
        $this->hashCallback = $value;
    }

    /**
     * Whether to use symbolic link to publish asset files.
     *
     * @param bool $value
     *
     * {@see linkAssets}
     */
    public function setLinkAssets(bool $value): void
    {
        $this->linkAssets = $value;
    }

    /**
     * Generate a CRC32 hash for the directory path. Collisions are higher than MD5 but generates a much smaller hash
     * string.
     *
     * @param string $path The string to be hashed.
     *
     * @return string The hashed string.
     */
    private function hash(string $path): string
    {
        if (is_callable($this->hashCallback)) {
            return ($this->hashCallback)($path);
        }

        $path = (is_file($path) ? dirname($path) : $path) . FileHelper::lastModifiedTime($path);

        return sprintf('%x', crc32($path . '|' . $this->linkAssets));
    }

    /**
     * Publishes a bundle directory.
     *
     * @param AssetBundle $bundle The asset bundle instance.
     *
     * @throws Exception If the asset to be published does not exist.
     *
     * @return array The path directory and the URL that the asset is published as.
     */
    private function publishBundleDirectory(AssetBundle $bundle): array
    {
        $src = $this->aliases->get((string) $bundle->sourcePath);
        $dir = $this->hash($src);
        $dstDir = "{$this->aliases->get((string) $bundle->basePath)}/{$dir}";

        if ($this->linkAssets) {
            if (!is_dir($dstDir)) {
                FileHelper::ensureDirectory(dirname($dstDir), $this->dirMode);
                try { // fix #6226 symlinking multi threaded
                    symlink($src, $dstDir);
                } catch (Exception $e) {
                    if (!is_dir($dstDir)) {
                        throw $e;
                    }
                }
            }
        } elseif (
            !empty($bundle->publishOptions['forceCopy'])
            || ($this->forceCopy && !isset($bundle->publishOptions['forceCopy']))
            || !is_dir($dstDir)
        ) {
            FileHelper::copyDirectory($src, $dstDir, array_merge($bundle->publishOptions, [
                'dirMode' => $this->dirMode,
                'fileMode' => $this->fileMode,
                'copyEmptyDirectories' => false,
            ]));
        }

        return [$dstDir, "{$this->aliases->get((string) $bundle->baseUrl)}/{$dir}"];
    }
}
