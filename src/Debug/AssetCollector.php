<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Debug;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Yii\Debug\Collector\CollectorTrait;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;

use function count;

final class AssetCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $assetBundles = [];

    public function getCollected(): array
    {
        return $this->assetBundles;
    }

    public function collect(AssetBundle $assetBundle): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->assetBundles[] = $assetBundle;
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'asset' => [
                'bundles' => [
                    'total' => count($this->assetBundles),
                ],
            ],
        ];
    }

    private function reset(): void
    {
        $this->assetBundles = [];
    }
}
