<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use RuntimeException;

/**
 * The AssetExporterInterface must be implemented by asset export classes. The job of such class is to export
 * asset bundle file paths into a format readable by third party tools such as Webpack.
 */
interface AssetExporterInterface
{
    /**
     * Exports asset bundle file paths {@see AssetBundle::$export}.
     *
     * All dependencies must be created, and path aliases for {@see AssetBundle} properties are resolved
     * {@see AssetUtil::resolvePathAliases()}. When using {@see AssetManager::export()}, all dependencies
     * and path aliases will be automatically resolved.
     *
     * @param AssetBundle[] $assetBundles The asset bundle instances or extending it to export.
     *
     * @throws RuntimeException Should be thrown if an error occurred during the export.
     */
    public function export(array $assetBundles): void;
}
