<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Files\FileHelper;

use function array_merge;
use function is_file;

/**
 * `AssetLoader` is responsible for executing the loading of the assets from {@see AssetBundle::$basePath} to
 * {@see AssetBundle::$baseUrl}.
 */
final class AssetLoader implements AssetLoaderInterface
{
    /**
     * @var array The options that will be passed to {@see \Yiisoft\View\WebView::registerCssFile()}
     * when registering the CSS files all assets bundle.
     */
    private array $cssDefaultOptions = [];

    private ?int $cssDefaultPosition = null;

    /**
     * @var array The options that will be passed to {@see \Yiisoft\View\WebView::registerJsFile()}
     * when registering the JS files all assets bundle.
     */
    private array $jsDefaultOptions = [];

    private ?int $jsDefaultPosition = null;

    /**
     * @param Aliases $aliases The aliases instance.
     * @param bool $appendTimestamp Whether to append a timestamp to the URL of every published asset. See
     *     {@see withAppendTimestamp()}.
     * @param array<string, string> $assetMap Mapping from source asset files to target asset files. See
     *     {@see withAssetMap()}.
     * @param string|null $basePath The root directory storing the asset files. See {@see withBasePath()}.
     * @param string|null $baseUrl The base URL that can be used to access the asset files. See {@see withBaseUrl()}.
     */
    public function __construct(
        private readonly Aliases $aliases,
        private bool $appendTimestamp = false,
        private array $assetMap = [],
        private ?string $basePath = null,
        private ?string $baseUrl = null
    ) {
    }

    public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
    {
        if (!$bundle->cdn && empty($this->basePath) && empty($bundle->basePath)) {
            throw new InvalidConfigException(
                'basePath must be set in AssetLoader->withBasePath($path) or ' .
                'AssetBundle property public ?string $basePath = $path'
            );
        }

        if (!$bundle->cdn && $this->baseUrl === null && $bundle->baseUrl === null) {
            throw new InvalidConfigException(
                'baseUrl must be set in AssetLoader->withBaseUrl($path) or ' .
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

        if (!AssetUtil::isRelative($assetPath) || str_starts_with($assetPath, '/')) {
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
        $bundle->cssPosition ??= $this->cssDefaultPosition;

        $bundle->jsOptions = array_merge($bundle->jsOptions, $this->jsDefaultOptions);
        $bundle->jsPosition ??= $this->jsDefaultPosition;

        return $bundle;
    }

    /**
     * Returns a new instance with the specified append timestamp.
     *
     * @param bool $appendTimestamp Whether to append a timestamp to the URL of every published asset. Default is `false`.
     * When this is true, the URL of a published asset may look like `/path/to/asset?v=timestamp`, where `timestamp`
     * is the last modification time of the published asset file. You normally would want to set this property to true
     * when you have enabled HTTP caching for assets, because it allows you to bust caching when the assets are updated.
     */
    public function withAppendTimestamp(bool $appendTimestamp): self
    {
        $new = clone $this;
        $new->appendTimestamp = $appendTimestamp;
        return $new;
    }

    /**
     * Returns a new instance with the specified asset map.
     *
     * @param array<string, string> $assetMap Mapping from source asset files (keys) to target asset files (values).
     *
     * Default is empty array. This property is provided to support fixing incorrect asset file paths in some
     * asset bundles. When an asset bundle is registered with a view, each relative asset file in its
     * {@see AssetBundle::$css} and {@see AssetBundle::$js} arrays will be examined against this map.
     * If any of the keys is found to be the last part of an asset file (which is prefixed with
     * {@see AssetBundle::$sourcePath} if available), the corresponding value will replace the asset
     * and be registered with the view. For example, an asset file `my/path/to/jquery.js` matches a key `jquery.js`.
     *
     * Note that the target asset files should be absolute URLs, domain relative URLs (starting from '/') or paths
     * relative to {@see withBaseUrl()} and {@see withBasePath()}.
     *
     * In the following example, any assets ending with `jquery.min.js` will be replaced with `jquery/dist/jquery.js`
     * which is relative to {@see withBaseUrl()} and {@see withBasePath()}.
     *
     * ```php
     * [
     *     'jquery.min.js' => 'jquery/dist/jquery.js',
     * ]
     * ```
     */
    public function withAssetMap(array $assetMap): self
    {
        $new = clone $this;
        $new->assetMap = $assetMap;
        return $new;
    }

    /**
     * Returns a new instance with the specified base path.
     *
     * @param string|null $basePath The root directory storing the asset files. Default is `null`.
     */
    public function withBasePath(?string $basePath): self
    {
        $new = clone $this;
        $new->basePath = $basePath;
        return $new;
    }

    /**
     * Returns a new instance with the specified base URL.
     *
     * @param string|null $baseUrl The base URL that can be used to access the asset files. Default is `null`.
     */
    public function withBaseUrl(?string $baseUrl): self
    {
        $new = clone $this;
        $new->baseUrl = $baseUrl;
        return $new;
    }

    /**
     * Returns a new instance with the specified global `$css` default options for all assets bundle.
     *
     * @param array $cssDefaultOptions The options that will be passed to
     * {@see \Yiisoft\View\WebView::registerCssFile()} when registering the CSS files all assets bundle.
     */
    public function withCssDefaultOptions(array $cssDefaultOptions): self
    {
        $new = clone $this;
        $new->cssDefaultOptions = $cssDefaultOptions;
        return $new;
    }

    /**
     * @param int|null $position Specifies where the `<style>` tag should be inserted in a page.
     *
     * @see AssetBundle::$cssPosition
     */
    public function withCssDefaultPosition(?int $position): self
    {
        $new = clone $this;
        $new->cssDefaultPosition = $position;
        return $new;
    }

    /**
     * Returns a new instance with the specified global `$js` default options for all assets bundle.
     *
     * @param array $jsDefaultOptions The options that will be passed to {@see \Yiisoft\View\WebView::registerJsFile()}
     * when registering the JS files all assets bundle.
     */
    public function withJsDefaultOptions(array $jsDefaultOptions): self
    {
        $new = clone $this;
        $new->jsDefaultOptions = $jsDefaultOptions;
        return $new;
    }

    /**
     * @param int|null $position Specifies where the `<script>` tag should be inserted in a page.
     *
     * @see AssetBundle::$jsPosition
     */
    public function withJsDefaultPosition(?int $position): self
    {
        $new = clone $this;
        $new->jsDefaultPosition = $position;
        return $new;
    }

    /**
     * If the asset bundle does not have a {@see AssetBundle::$basePath} set, the value set with {@see withBasePath()}
     * is returned.
     */
    private function getBundleBasePath(AssetBundle $bundle): ?string
    {
        if ($bundle->basePath === null && $this->basePath === null) {
            return null;
        }

        return $this->aliases->get($bundle->basePath ?? (string) $this->basePath);
    }

    /**
     * If the asset bundle does not have a {@see AssetBundle::$baseUrl} set, the value set with {@see withBaseUrl()}
     * is returned.
     */
    private function getBundleBaseUrl(AssetBundle $bundle): ?string
    {
        if ($bundle->baseUrl === null && $this->baseUrl === null) {
            return null;
        }

        return $this->aliases->get($bundle->baseUrl ?? (string) $this->baseUrl);
    }
}
