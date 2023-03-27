<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\Debug;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\Debug\AssetCollector;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

final class AssetCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param AssetCollector|CollectorInterface $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(new AssetBundle());
    }

    protected function getCollector(): CollectorInterface
    {
        return new AssetCollector();
    }
}
