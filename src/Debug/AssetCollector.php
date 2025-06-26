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

    /**
     * @psalm-var list<AssetBundle>
     */
    private array $assetBundles = [];

    /**
     * @psalm-return list<AssetBundle>
     */
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

    /**
     * @psalm-return array{bundles?: array{total: int}}
     */
    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'bundles' => [
                'total' => count($this->assetBundles),
            ],
        ];
    }

    /**
     * https://github.com/phpstan/phpstan/issues/12201
     * @phpstan-ignore method.unused
     */
    private function reset(): void
    {
        $this->assetBundles = [];
    }
}
