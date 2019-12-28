### AssetManager without di-container:

You can configure AssetManager without di-container not recommended, keep in mind that you must inject the dependencies in the constructors of each class.

```php
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;

/**
 * Inyect dependencies in constructor:
 * 
 * \Yisoft\Aliases\Aliases $aliases, \Psr\Log\LoggerInterface $logger
 */
$converter = new AssetConverter($aliases, $logger);

/**
 * Inyect dependencies in constructor:
 * 
 * \Yisoft\Aliases\Aliases $aliases
 */
$publisher = new AssetPublisher($aliases);

/**
 * Inyect dependencies in constructor:
 * 
 * \Yisoft\Aliases\Aliases $aliases, \Psr\Log\LoggerInterface $logger
 */
$assetManager = new AssetManager($aliases, $logger);

$assetManager->setConverter($converter);
$assetManager->setPublisher($publisher);
```
