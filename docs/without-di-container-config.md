### CONFIG ASSETMANAGER WITHOUT DI-CONTAINER: ###

```php
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;

$publisher = new AssetPublisher();

/*
 * Inyect dependencies in constructor:
 * 
 * \Yisoft\Aliases\Aliases, \Psr\Log\LoggerInterface
 */
$assetManager = new AssetManager($aliases, $logger);
$assetManager->setPublisher($publisher);
```
