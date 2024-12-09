<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Exporter;

use RuntimeException;
use Yiisoft\Assets\AssetExporterInterface;
use Yiisoft\Assets\AssetUtil;

/**
 * Exports the file paths of asset bundles {@see AssetBundle::$export}, converting them to `import '/path/to/file';`
 * expressions and placing them in the specified JavaScript file for later loading into Webpack.
 *
 * @link https://webpack.js.org/concepts/#entry
 */
final class WebpackAssetExporter implements AssetExporterInterface
{
    /**
     * @param string $targetFile The full path to the target JavaScript file.
     */
    public function __construct(
        private readonly string $targetFile,
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException If an error occurred while writing to the JavaScript file.
     */
    public function export(array $assetBundles): void
    {
        $imports = '';

        foreach (AssetUtil::extractFilePathsForExport($assetBundles) as $file) {
            $imports .= "import '{$file}';\n";
        }

        AssetUtil::exportToFile($this->targetFile, $imports);
    }
}
