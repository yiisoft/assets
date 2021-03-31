<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Files\FileHelper;

use function array_merge;
use function is_file;
use function strncmp;

/**
 * AssetLoader is responsible for executing the loading of the assets
 * from {@see AssetBundle::$basePath} to {@see AssetBundle::$baseUrl}.
 */
final class AssetLoader implements AssetLoaderInterface
{
    private Aliases $aliases;

    /**
     * @var bool Whether to append a timestamp to the URL of every published asset. When this is true, the URL of a
     * published asset may look like `/path/to/asset?v=timestamp`, where `timestamp` is the last modification time
     * of the published asset file. You normally would want to set this property to true when you have enabled
     * HTTP caching for assets, because it allows you to bust caching when the assets are updated.
     */
    private bool $appendTimestamp = false;

    /**
     * @var array<string, string> Mapping from source asset files (keys) to target asset files (values).
     *
     * This property is provided to support fixing incorrect asset file paths in some asset bundles. When an asset
     * bundle is registered with a view, each relative asset file in its {@see AssetBundle::$css} and
     * {@see AssetBundle::$js} arrays will be examined against this map. If any of the keys is found to be the last
     * part of an asset file (which is prefixed with {@see AssetBundle::$sourcePath} if available), the corresponding
     * value will replace the asset and be registered with the view. For example, an asset file `my/path/to/jquery.js`
     * matches a key `jquery.js`.
     *
     * Note that the target asset files should be absolute URLs, domain relative URLs (starting from '/') or paths
     * relative to {@see $baseUrl} and {@see $basePath}.
     *
     * In the following example, any assets ending with `jquery.min.js` will be replaced with `jquery/dist/jquery.js`
     * which is relative to {@see $baseUrl} and {@see $basePath}.
     *
     * ```php
     * [
     *     'jquery.min.js' => 'jquery/dist/jquery.js',
     * ]
     * ```
     */
    private array $assetMap = [];

    /**
     * @var string|null The root directory storing the asset files.
     */
    private ?string $basePath = null;

    /**
     * @var string|null The base URL that can be used to access the asset files.
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

    public function __construct(Aliases $aliases)
    {
        $this->aliases = $aliases;
    }

    public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
    {
        if (!$bundle->cdn && empty($this->basePath) && empty($bundle->basePath)) {
            throw new InvalidConfigException(
                'basePath must be set in AssetLoader->setBasePath($path) or ' .
                'AssetBundle property public ?string $basePath = $path'
            );
        }

        if (!$bundle->cdn && $this->baseUrl === null && $bundle->baseUrl === null) {
            throw new InvalidConfigException(
                'baseUrl must be set in AssetLoader->setBaseUrl($path) or ' .
                'AssetBundle property public ?string $baseUrl = $path'
            );
        }

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

        $path = "{$this->getBundleBasePath($bundle)}/{$assetPath}";
        $url = "{$this->getBundleBaseUrl($bundle)}/{$assetPath}";

        if (!is_file($path)) {
            throw new InvalidConfigException("Asset files not found: \"{$path}\".");
        }

        if ($this->appendTimestamp && ($timestamp = FileHelper::lastModifiedTime($path)) > 0) {
            return "{$url}?v={$timestamp}";
        }

        return $url;
    }

    public function loadBundle(string $name, array $config = []): AssetBundle
    {
        $bundle = AssetUtil::createAsset($name, $config);

        $bundle->basePath = $this->getBundleBasePath($bundle);
        $bundle->baseUrl = $this->getBundleBaseUrl($bundle);
        $bundle->sourcePath = $bundle->sourcePath === null ? null : $this->aliases->get($bundle->sourcePath);

        $bundle->cssOptions = array_merge($bundle->cssOptions, $this->cssDefaultOptions);
        $bundle->jsOptions = array_merge($bundle->jsOptions, $this->jsDefaultOptions);

        return $bundle;
    }

    /**
     * Sets whether to append a timestamp to the URL of every loaded asset.
     *
     * @param bool $value
     *
     * {@see $appendTimestamp}
     */
    public function setAppendTimestamp(bool $value): void
    {
        $this->appendTimestamp = $value;
    }

    /**
     * Sets the map for mapping from source asset files (keys) to target asset files (values).
     *
     * @param array<string, string> $value
     *
     * {@see $assetMap}
     */
    public function setAssetMap(array $value): void
    {
        $this->assetMap = $value;
    }

    /**
     * Sets the root directory storing the asset files.
     *
     * @param string|null $value
     *
     * {@see $basePath}
     */
    public function setBasePath(?string $value): void
    {
        $this->basePath = $value;
    }

    /**
     * Sets the base URL that can be used to access the asset files.
     *
     * @param string|null $value
     *
     * {@see $baseUrl}
     */
    public function setBaseUrl(?string $value): void
    {
        $this->baseUrl = $value;
    }

    /**
     * Sets the global `$css` default options for all assets bundle.
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
     * Sets the global `$js` default options for all assets bundle.
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
     * If the asset bundle does not have a {@see AssetBundle::$basePath} set, the {@see $basePath} value is returned.
     *
     * @param AssetBundle $bundle
     *
     * @return string|null
     */
    private function getBundleBasePath(AssetBundle $bundle): ?string
    {
        if ($bundle->basePath === null && $this->basePath === null) {
            return null;
        }

        return $this->aliases->get($bundle->basePath ?? (string) $this->basePath);
    }

    /**
     * If the asset bundle does not have a {@see AssetBundle::$baseUrl} set, the {@see $baseUrl} value is returned.
     *
     * @param AssetBundle $bundle
     *
     * @return string|null
     */
    private function getBundleBaseUrl(AssetBundle $bundle): ?string
    {
        if ($bundle->baseUrl === null && $this->baseUrl === null) {
            return null;
        }

        return $this->aliases->get($bundle->baseUrl ?? (string) $this->baseUrl);
    }
}
