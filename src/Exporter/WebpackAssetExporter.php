<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Exporter;

use RuntimeException;
use Yiisoft\Assets\AssetExporterInterface;
use Yiisoft\Assets\AssetUtil;

use function array_shift;
use function array_unique;
use function implode;
use function is_array;

/**
 * Exports the CSS and JavaScript file paths of asset bundles, converting them to `import '/path/to/file';`
 * expressions and placing them in the specified JavaScript file for later loading into Webpack.
 *
 * {@see https://webpack.js.org/concepts/#entry}
 */
final class WebpackAssetExporter implements AssetExporterInterface
{
    /**
     * @var string The full path to the target JavaScript file.
     */
    private string $targetFile;

    /**
     * @param string $targetFile The full path to the target JavaScript file.
     */
    public function __construct(string $targetFile)
    {
        $this->targetFile = $targetFile;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException If an error occurred while writing to the JavaScript file.
     */
    public function export(array $assetBundles): void
    {
        $imports = [];

        foreach ($assetBundles as $bundle) {
            if ($bundle->cdn || empty($bundle->sourcePath)) {
                continue;
            }

            foreach ($bundle->css as $css) {
                if ($css !== null) {
                    $file = is_array($css) ? array_shift($css) : $css;
                    $imports[] = "import '{$bundle->sourcePath}/{$file}';";
                }
            }

            foreach ($bundle->js as $js) {
                if ($js !== null) {
                    $file = is_array($js) ? array_shift($js) : $js;
                    $imports[] = "import '{$bundle->sourcePath}/{$file}';";
                }
            }
        }

        AssetUtil::exportToFile($this->targetFile, implode("\n", array_unique($imports)) . "\n");
    }
}
