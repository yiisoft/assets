<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Exporter;

use Yiisoft\Assets\AssetExporterInterface;

/**
 * Exports asset bundles with the values of all properties using a custom PHP callable.
 */
final class AssetCallableExporter implements AssetExporterInterface
{
    /**
     * @var callable The custom PHP callable.
     *
     * PHP callable supports the following signature:
     *
     * ```php
     * function (array $assetBundles): void;
     * ```
     *
     * Learn more about the `$assetBundles`, see description the {@see AssetExporterInterface::export()}.
     */
    private $export;

    /**
     * @param callable $export The custom PHP callable {@see $export}.
     */
    public function __construct(callable $export)
    {
        $this->export = $export;
    }

    public function export(array $assetBundles): void
    {
        ($this->export)($assetBundles);
    }
}
