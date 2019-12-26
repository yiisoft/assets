<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Files\FileHelper;

/**
 * AssetPublisher is responsible for executing the publication of the assets from {@see sourcePath} to {@see basePath}.
 */
final class AssetPublisher
{
    /**
     * @var AssetManager $assetManager
     */
    private AssetManager $assetManager;

    /**
     * @var string|null the root directory storing the published asset files.
     */
    private ?string $basePath;

    /**
     * @var string|null the root directory storing the published asset files.
     */
    private ?string $baseUrl;

    /**
     * @var array published assets
     */
    private array $published = [];

    public function __construct(AssetManager $assetManager)
    {
        $this->assetManager = $assetManager;
    }

    /**
     * Returns the actual URL for the specified asset.
     *
     * The actual URL is obtained by prepending either {@see AssetBundle::$baseUrl} or {@see AssetManager::$baseUrl} to
     * the given asset path.
     *
     * @param AssetBundle $bundle the asset bundle which the asset file belongs to.
     * @param string $pathAsset the asset path. This should be one of the assets listed in {@see AssetBundle::$js} or
     * {@see AssetBundle::$css}.
     *
     * @return string the actual URL for the specified asset.
     * @throws InvalidConfigException
     */
    public function getAssetUrl(AssetBundle $bundle, string $pathAsset): string
    {
        $basePath = $this->assetManager->getAliases()->get($bundle->basePath);
        $baseUrl = $this->assetManager->getAliases()->get($bundle->baseUrl);

        if ($asset = AssetUtil::resolveAsset($bundle, $pathAsset, $this->assetManager->getAssetMap())) {
            $pathAsset = $asset;
        }

        if (!AssetUtil::isRelative($pathAsset) || strncmp($pathAsset, '/', 1) === 0) {
            return $pathAsset;
        }

        if (!is_file("$basePath/$pathAsset")) {
            throw new InvalidConfigException("Asset files not found: '$basePath/$pathAsset.'");
        }

        if ($this->assetManager->getAppendTimestamp()  && ($timestamp = @filemtime("$basePath/$pathAsset")) > 0) {
            return "$baseUrl/$pathAsset?v=$timestamp";
        }

        return "$baseUrl/$pathAsset";
    }

    /**
     * Loads asset bundle class by name.
     *
     * @param string $name bundle name.
     * @param array $config bundle object configuration.
     *
     * @return AssetBundle
     *
     * @throws InvalidConfigException
     */
    public function loadBundle(string $name, array $config = []): AssetBundle
    {
        /** @var AssetBundle $bundle */
        $bundle = new $name();

        foreach ($config as $property => $value) {
            $bundle->$property = $value;
        }

        $this->checkBasePath($bundle->basePath);
        $this->checkBaseUrl($bundle->baseUrl);

        if (!empty($bundle->sourcePath)) {
            [$bundle->basePath, $bundle->baseUrl] = ($this->publish($bundle));
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
     * @param AssetBundle $bundle the asset (file or directory) to be read.
     *
     * - only: array, list of patterns that the file paths should match if they want to be copied.
     *
     * @return array the path (directory or file path) and the URL that the asset is published as.
     * @throws InvalidConfigException if the asset to be published does not exist.
     *
     */
    public function publish(AssetBundle $bundle): array
    {
        $this->checkBasePath($bundle->basePath);
        $this->checkBaseUrl($bundle->baseUrl);

        if (isset($this->published[$bundle->sourcePath])) {
            return $this->published[$bundle->sourcePath];
        }

        if (!file_exists($this->assetManager->getAliases()->get($bundle->sourcePath))) {
            throw new InvalidConfigException("The sourcePath to be published does not exist: $bundle->sourcePath");
        }

        return $this->published[$bundle->sourcePath] = $this->publishDirectory(
            $bundle->sourcePath,
            $bundle->publishOptions
        );
    }

    /**
     * Returns the published path of a file path.
     *
     * This method does not perform any publishing. It merely tells you if the file or directory is published, where it
     * will go.
     *
     * @param string $sourcePath directory or file path being published.
     *
     * @return string|null string the published file path. Null if the file or directory does not exist
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
     * This method does not perform any publishing. It merely tells you if the file path is published, what the URL will
     * be to access it.
     *
     * @param string $sourcePath directory or file path being published
     *
     * @return string|null string the published URL for the file or directory. Null if the file or directory does not
     * exist.
     */
    public function getPublishedUrl(string $sourcePath): ?string
    {
        if (isset($this->published[$sourcePath])) {
            return $this->published[$sourcePath][1];
        }

        return null;
    }

    /**
     * Registers the CSS and JS files with the given view.
     *
     * @param AssetBundle $bundle the asset files are to be registered in the view.
     *
     * @return void
     */
    public function registerAssetFiles(AssetBundle $bundle): void
    {
        foreach ($bundle->js as $js) {
            if (is_array($js)) {
                $file = array_shift($js);
                $options = array_merge($bundle->jsOptions, $js);
                $this->assetManager->registerJsFile($this->getAssetUrl($bundle, $file), $options);
            } elseif ($js !== null) {
                $this->assetManager->registerJsFile($this->getAssetUrl($bundle, $js), $bundle->jsOptions);
            }
        }

        foreach ($bundle->css as $css) {
            if (is_array($css)) {
                $file = array_shift($css);
                $options = array_merge($bundle->cssOptions, $css);
                $this->assetManager->registerCssFile($this->getAssetUrl($bundle, $file), $options);
            } elseif ($css !== null) {
                $this->assetManager->registerCssFile($this->getAssetUrl($bundle, $css), $bundle->cssOptions);
            }
        }
    }

    private function checkBasePath(?string $basePath): void
    {
        if (empty($basePath) && empty($this->assetManager->getBasePath())) {
            throw new InvalidConfigException(
                'basePath must be set in AssetManager->setBasePath($path) or ' .
                'AssetBundle property public ?string $basePath = $path'
            );
        }
        if (empty($basePath)) {
            $this->basePath = $this->assetManager->getBasePath();
        } else {
            $this->basePath = $this->assetManager->getAliases()->get($basePath);
        }
    }

    private function checkBaseUrl(?string $baseUrl): void
    {
        if (empty($baseUrl) && empty($this->assetManager->getBaseUrl())) {
            throw new InvalidConfigException(
                'baseUrl must be set in AssetManager->setBaseUrl($path) or ' .
                'AssetBundle property public ?string $baseUrl = $path'
            );
        }
        if (empty($baseUrl)) {
            $this->baseUrl = $this->assetManager->getBaseUrl();
        } else {
            $this->baseUrl = $this->assetManager->getAliases()->get($baseUrl);
        }
    }

    /**
     * Generate a CRC32 hash for the directory path. Collisions are higher than MD5 but generates a much smaller hash
     * string.
     *
     * @param string $path string to be hashed.
     *
     * @return string hashed string.
     */
    private function hash(string $path): string
    {
        if (is_callable($this->assetManager->getHashCallback())) {
            return call_user_func($this->assetManager->getHashCallback(), $path);
        }

        $path = (is_file($path) ? dirname($path) : $path) . @filemtime($path);

        return sprintf('%x', crc32($path . '|' . $this->assetManager->getLinkAssets()));
    }

    /**
     * Publishes a directory.
     *
     * @param string $src the asset directory to be published
     * @param array $options the options to be applied when publishing a directory. The following options are
     * supported:
     *
     * - only: patterns that the file paths should match if they want to be copied.
     *
     * @return array the path directory and the URL that the asset is published as.
     *
     * @throws \Exception if the asset to be published does not exist.
     */
    private function publishDirectory(string $src, array $options): array
    {
        $src = $this->assetManager->getAliases()->get($src);
        $dir = $this->hash($src);
        $dstDir = $this->basePath . '/' . $dir;

        if ($this->assetManager->getLinkAssets()) {
            if (!is_dir($dstDir)) {
                FileHelper::createDirectory(dirname($dstDir), $this->assetManager->getDirMode());
                try { // fix #6226 symlinking multi threaded
                    symlink($src, $dstDir);
                } catch (\Exception $e) {
                    if (!is_dir($dstDir)) {
                        throw $e;
                    }
                }
            }
        } elseif (!empty($options['forceCopy']) ||
            ($this->assetManager->getForceCopy() && !isset($options['forceCopy'])) || !is_dir($dstDir)) {
            $opts = array_merge(
                $options,
                [
                    'dirMode' => $this->assetManager->getDirMode(),
                    'fileMode' => $this->assetManager->getFileMode(),
                    'copyEmptyDirectories' => false,
                ]
            );

            FileHelper::copyDirectory($src, $dstDir, $opts);
        }

        return [$dstDir, $this->baseUrl . '/' . $dir];
    }
}
