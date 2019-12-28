### Config \Yiisoft\View\WebView::setdefaultParameters():

ViewFactory.php:
```php
declare(strict_types=1);

namespace App\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\Theme;
use Yiisoft\View\View;
use Yiisoft\View\WebView;

class ViewFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $aliases = $container->get(Aliases::class);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $logger = $container->get(LoggerInterface::class);
        $theme = $container->get(Theme::class);

        $webView = new WebView($aliases->get('@views'), $theme, $eventDispatcher, $logger);

        /**
         * Passes {@see UrlGeneratorInterface} and {@see AssetManager} to view files.
         * It will be available as $urlGenerator and $assetManager in all views or layout.
         */
        $webView->setDefaultParameters([
            'aliases' => $aliases,
            'assetManager' => $container->get(AssetManager::class),
            'urlGenerator' => $container->get(UrlGeneratorInterface::class),
        ]);


        return $webView;
    }
}
```
