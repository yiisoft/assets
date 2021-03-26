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
use function strncmp;
use function symlink;

/**
 * AssetPublisher is responsible for executing the publication of the assets
 * from {@see AssetBundle::sourcePath} to {@see AssetBundle::basePath}.
 */
final class AssetPublisher implements AssetPublisherInterface
{
    private Aliases $aliases;

    /**
     * @var bool Whether to append a timestamp to the URL of every published asset. When this is true, the URL of a
     * published asset may look like `/path/to/asset?v=timestamp`, where `timestamp` is the last modification time of
     * the published asset file. You normally would want to set this property to true when you have enabled HTTP caching
     * for assets, because it allows you to bust caching when the assets are updated.
     */
    private bool $appendTimestamp = false;

    /**
     * @var array Mapping from source asset files (keys) to target asset files (values).
     *
     * This property is provided to support fixing incorrect asset file paths in some asset bundles. When an asset
     * bundle is registered with a view, each relative asset file in its {@see AssetBundle::css} and
     * {@see AssetBundle::js} arrays will be examined against this map. If any of the keys is found to be the last
     * part of an asset file (which is prefixed with {@see AssetBundle::sourcePath} if available), the corresponding
     * value will replace the asset and be registered with the view. For example, an asset file `my/path/to/jquery.js`
     * matches a key `jquery.js`.
     *
     * Note that the target asset files should be absolute URLs, domain relative URLs (starting from '/') or paths
     * relative to {@see baseUrl} and {@see basePath}.
     *
     * In the following example, any assets ending with `jquery.min.js` will be replaced with `jquery/dist/jquery.js`
     * which is relative to {@see baseUrl} and {@see basePath}.
     *
     * ```php
     * [
     *     'jquery.min.js' => 'jquery/dist/jquery.js',
     * ]
     * ```
     */
    private array $assetMap = [];

    /**
     * @var string|null The root directory storing the published asset files.
     */
    private ?string $basePath = null;

    /**
     * @var string|null The root directory storing the published asset files.
     */
    private ?string $baseUrl = null;

    /**
     * @var array The options that will be passed to {@see \Yiisoft\View\WebView::registerCssFile()}
     * when registering the CSS files all assets bundle.
     */
    private array $cssDefaultOptions = [];

    /**
     * @var array The options that will be passed to {@see \Yiisoft\View\WebView::registerJsFile()}
     * when registering the JS files all assets bundle.
     */
    private array $jsDefaultOptions = [];

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
    public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
    {
        $this->checkBundleData($bundle);

        $asset = AssetUtil::resolveAsset($bundle, $assetPath, $this->assetMap);

        if (!empty($asset)) {
            $assetPath = $asset;
        }

        if ($bundle->cdn) {
            return $bundle->baseUrl === null
                ? $assetPath
                : $bundle->baseUrl . '/' . $assetPath;
        }

        if (!AssetUtil::isRelative($assetPath) || strncmp($assetPath, '/', 1) === 0) {
            return $assetPath;
        }

        $path = $this->getBundleBasePath($bundle) . '/' . $assetPath;
        $url = $this->getBundleBaseUrl($bundle) . '/' . $assetPath;

        if (!is_file($path)) {
            throw new InvalidConfigException("Asset files not found: \"{$path}\".");
        }

        if ($this->appendTimestamp && ($timestamp = FileHelper::lastModifiedTime("$path")) > 0) {
            return $url . '?v=' . $timestamp;
        }

        return $url;
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
     * Loads asset bundle class by name.
     *
     * @param string $name The asset bundle name.
     * @param array $config The asset bundle instance configuration.
     *
     * @throws InvalidConfigException For invalid asset bundle configuration.
     *
     * @return AssetBundle The asset bundle instance.
     */
    public function loadBundle(string $name, array $config = []): AssetBundle
    {
        $bundle = AssetUtil::createAsset($name, $config);

        $bundle->cssOptions = array_merge($bundle->cssOptions, $this->cssDefaultOptions);
        $bundle->jsOptions = array_merge($bundle->jsOptions, $this->jsDefaultOptions);

        if ($bundle->cdn) {
            return $bundle;
        }

        if (!empty($bundle->sourcePath)) {
            [$bundle->basePath, $bundle->baseUrl] = $this->publish($bundle);
        }

        return $bundle;
    }

    /**
     * Publishes a file or a directory.
     *
     * This method will copy the specified file or directory to {@see basePath} so that it can be accessed via the Web
     * server.
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
    public function publish(AssetBundle $bundle): array
    {
        if (empty($bundle->sourcePath)) {
            throw new InvalidConfigException(
                'The sourcePath must be defined in AssetBundle property public ?string $sourcePath = $path.'
            );
        }

        if (isset($this->published[$bundle->sourcePath])) {
            return $this->published[$bundle->sourcePath];
        }

        $this->checkBundleData($bundle);

        if (!file_exists($this->aliases->get($bundle->sourcePath))) {
            throw new InvalidConfigException("The sourcePath to be published does not exist: {$bundle->sourcePath}");
        }

        return $this->published[$bundle->sourcePath] = $this->publishBundleDirectory($bundle);
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
     * Append a timestamp to the URL of every published asset.
     *
     * @param bool $value
     *
     * {@see appendTimestamp}
     */
    public function setAppendTimestamp(bool $value): void
    {
        $this->appendTimestamp = $value;
    }

    /**
     * Mapping from source asset files (keys) to target asset files (values).
     *
     * @param array $value
     *
     * {@see assetMap}
     */
    public function setAssetMap(array $value): void
    {
        $this->assetMap = $value;
    }

    /**
     * The root directory storing the published asset files.
     *
     * @param string|null $value
     *
     * {@see basePath}
     */
    public function setBasePath(?string $value): void
    {
        $this->basePath = $value;
    }

    /**
     * The base URL through which the published asset files can be accessed.
     *
     * @param string|null $value
     *
     * {@see baseUrl}
     */
    public function setBaseUrl(?string $value): void
    {
        $this->baseUrl = $value;
    }

    /**
     * The global $css default options for all assets bundle.
     *
     * @param array $value
     *
     * {@see $cssDefaultOptions}
     */
    public function setCssDefaultOptions(array $value): void
    {
        $this->cssDefaultOptions = $value;
    }

    /**
     * The global $js default options for all assets bundle.
     *
     * @param array $value
     *
     * {@see $jsDefaultOptions}
     */
    public function setJsDefaultOptions(array $value): void
    {
        $this->jsDefaultOptions = $value;
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

    private function getBundleBasePath(AssetBundle $bundle): string
    {
        return $this->aliases->get((string) (empty($bundle->basePath) ? $this->basePath : $bundle->basePath));
    }

    private function getBundleBaseUrl(AssetBundle $bundle): string
    {
        return $this->aliases->get((string) ($bundle->baseUrl === null ? $this->baseUrl : $bundle->baseUrl));
    }

    /**
     * Verify the {@see basePath} and the {@see baseUrl} of AssetPublisher and AssetBundle is valid.
     *
     * @param AssetBundle $bundle
     *
     * @throws InvalidConfigException
     */
    private function checkBundleData(AssetBundle $bundle): void
    {
        if (!$bundle->cdn && empty($this->basePath) && empty($bundle->basePath)) {
            throw new InvalidConfigException(
                'basePath must be set in AssetPublisher->setBasePath($path) or ' .
                'AssetBundle property public ?string $basePath = $path'
            );
        }

        if (!$bundle->cdn && !isset($this->baseUrl) && $bundle->baseUrl === null) {
            throw new InvalidConfigException(
                'baseUrl must be set in AssetPublisher->setBaseUrl($path) or ' .
                'AssetBundle property public ?string $baseUrl = $path'
            );
        }
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
        $dstDir = $this->getBundleBasePath($bundle) . '/' . $dir;

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
            !empty($bundle->publishOptions['forceCopy']) ||
            ($this->forceCopy && !isset($bundle->publishOptions['forceCopy'])) ||
            !is_dir($dstDir)
        ) {
            $opts = array_merge(
                $bundle->publishOptions,
                [
                    'dirMode' => $this->dirMode,
                    'fileMode' => $this->fileMode,
                    'copyEmptyDirectories' => false,
                ]
            );

            FileHelper::copyDirectory($src, $dstDir, $opts);
        }

        return [$dstDir, $this->getBundleBaseUrl($bundle) . '/' . $dir];
    }
}
