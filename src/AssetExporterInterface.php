<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use RuntimeException;

/**
 * The AssetExporterInterface must be implemented by asset export classes. The job of such class is to export
 * bundles configuration into a format readable by third party tools such as Webpack.
 */
interface AssetExporterInterface
{
    /**
     * Exports asset bundles.
     *
     * All dependencies must be created, and path aliases for {@see AssetBundle} properties are resolved
     * {@see AssetUtil::resolvePathAliases()}. When using {@see AssetManager::export()}, all dependencies
     * and path aliases will be automatically resolved.
     *
     * @param array<string, AssetBundle> $assetBundles The asset bundle instances to export.
     * The array keys are the names of the asset bundles, usually class names (without leading backslash).
     * The array values are the corresponding instances of the {@see AssetBundle} or extending it.
     *
     * @throws RuntimeException Should be thrown if an error occurred during the export.
     */
    public function export(array $assetBundles): void;
}
