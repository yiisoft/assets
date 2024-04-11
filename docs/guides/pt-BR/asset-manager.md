# Gerente de ativos

O AssetManager resolve os pacotes de recursos registrados nele e fornece uma lista de arquivos para incluir no HTML.
Para uso geral, consulte [asset bundles](asset-bundles.md). Neste guia, nos concentraremos em configurá-lo.

A configuração pode ser feita de duas maneiras:

- Usando contêiner DI como [yiisoft/di](https://github.com/yiisoft/di)
- Criando uma classe manualmente

## Criando usando um contêiner

```php
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetLoader;
use Yiisoft\Assets\AssetLoaderInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Log\Logger;

return [
    Aliases::class => static fn () => new Aliases([
        '@root' => dirname(__DIR__),
        '@public' => '@root/public',
        '@assets' => '@public/assets',
        '@assetsUrl' => '/assets',
        '@npm' => '@root/node_modules',
    ]),

    LoggerInterface::class => Logger::class,
    AssetConverterInterface::class => AssetConverter::class,
    
    AssetLoaderInterface::class => static function (ContainerInterface $container) {
        $loader = new AssetLoader($container->get(Aliases::class));
        
        /**
         * Example settings options AssetLoader:
         *
         * $loader = $loader->withAppendTimestamp(true);
         * $loader = $loader->withAssetMap(['jquery.js' => 'https://code.jquery.com/jquery-3.4.1.js']);        
         * $loader = $loader->withBasePath('@assets');
         * $loader = $loader->withBaseUrl('@assetsUrl');
         * $loader = $loader->withCssDefaultOptions(['media' => 'screen', 'hreflang' => 'en');
         * $loader = $loader->withJsDefaultOptions(['async' => true, 'defer' => true);
         */
         
         return $loader;
    },
    
    AssetPublisherInterface::class => static function (ContainerInterface $container) {
        $publisher = new AssetPublisher($container->get(Aliases::class));

        /**
         * Example settings options AssetPublisher:
         *
         * $publisher = $publisher->withDirMode(0775);
         * $publisher = $publisher->withFileMode(0755);
         * $publisher = $publisher->withForceCopy(true);
         * $publisher = $publisher->withHashCallback(static fn () => 'hash');
         * $publisher = $publisher->withLinkAssets(true);
         */

        return $publisher;
    },

    AssetManager::class => static function (ContainerInterface $container) {
        $assetManager = new AssetManager(
            $container->get(Aliases::class),
            $container->get(AssetLoaderInterface::class),
        );
 
        $assetManager = $assetManager->withConverter($container->get(AssetConverterInterface::class));
        $assetManager = $assetManager->withPublisher($container->get(AssetPublisherInterface::class));

        return $assetManager;
    },
];
```

## Criando uma classe manualmente

```php
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetLoader;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;

/** 
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Psr\Log\LoggerInterface $logger
 */

$converter = new AssetConverter($aliases, $logger);
$loader = new AssetLoader($aliases);
$publisher = new AssetPublisher($aliases);


$assetManager = (new AssetManager($aliases, $loader))
    ->withConverter($converter)
    ->withPublisher($publisher)
;
```

## Especificando configurações adicionais

O gerenciador de ativos aceita dois parâmetros opcionais `$allowedBundleNames` e `$customizedBundles` no construtor:

```php
/** 
 * @var string[] $allowedBundleNames
 * @var array $customizedBundles
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Psr\Log\LoggerInterface $logger
 */

$assetManager = new \Yiisoft\Assets\AssetManager(
    $aliases,
    $logger,
    $allowedBundleNames, // Default to empty array.
    $customizedBundles // Default to empty array.
);
```

### Pacotes de recursos permitidos

`$allowedBundleNames` – Lista de nomes de pacotes de recursos permitidos. Se os nomes dos pacotes de ativos permitidos fossem especificados,
apenas esses pacotes de ativos ou suas dependências poderiam ser registrados e recebidos. Se  array estiver vazio,
então, quaisquer pacotes de ativos serão permitidos.

```php
$allowedBundleNames = [
    \App\Assets\BootstrapAsset::class,
    \App\Assets\MainAsset::class,
    \App\Assets\JqueryAsset::class,
];
```

Os pacotes de ativos especificados e todas as suas dependências serão permitidos, portanto você pode especificar os pacotes de nível superior
e não listar todas as dependências. Por exemplo, se `MainAsset` depende de `BootstrapAsset`,
e o `BootstrapAsset` depende do `JqueryAsset`, então você pode especificar
apenas o `MainAsset`.

```php
$allowedBundleNames = [
    \App\Assets\MainAsset::class,
];
```

O uso de pacotes de ativos permitidos permite publicar e exportar pacotes de ativos sem registrá-los manualmente.
Também é conveniente publicar ativos usando um comando de console, por exemplo,
para uma publicação única ao implantar um aplicativo.

### Personalização de pacotes de ativos

`$customizedBundles` - Configurações para personalizar pacotes de ativos. Ao carregar pacotes de ativos,
se houver uma configuração correspondente especificada aqui, a configuração será aplicada. O array de
chaves são os nomes dos pacotes de classes de ativos e os valores são matrizes com valores de propriedade modificados.

```php
$customizedBundles = [
    \App\Assets\JqueryAsset::class => [
        'sourcePath' => null, // No publish asset bundle.
        'js' => [
            [
                'https://code.jquery.com/jquery-3.4.1.js',
                'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                'crossorigin' => 'anonymous',
            ],
        ],
    ],
];
```

Os valores das propriedades `sourcePath` e `js` serão redefinidos para o pacote `App\Assets\JqueryAsset`,
e os valores das demais propriedades permanecerão inalterados.

> Se um valor for `false`, significa que o pacote de ativos correspondente está desativado e
> todos os valores de suas propriedades estarão vazios.

Para uso no [Yii framework](https://www.yiiframework.com/),
veja os arquivos de configuração: [`config/params.php`](../../../config/params.php) e [`config/web.php`](../../../config/di.php).

## Publicação de pacotes de recursos

Existem dois modos disponíveis para usar o gerenciador de ativos. Com e sem publisher.

Usando o publisher, o gestor publicará automaticamente os ativos e monitorará suas alterações. Isso é conveniente
quando seu aplicativo e ativos estão localizados no mesmo servidor e o PHP é responsável por todas as manipulações.
Este modo é usado por padrão no modelo de aplicativo [yiisoft/app](https://github.com/yiisoft/app).

```php
/** 
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Yiisoft\Assets\AssetLoaderInterface $loader
 * @var \Yiisoft\Assets\AssetPublisherInterface $publisher
 */

$assetManager = (new \Yiisoft\Assets\AssetManager($aliases, $loader))
    ->withPublisher($publisher)
;

$assetManager->register(\App\Assets\MainAsset::class);
// Or several in one pack:
$assetManager->registerMany([
    \App\Assets\BootstrapAsset::class,
    \App\Assets\MainAsset::class,
]);
```

Se você optar por não usar o publisher, deverá cuidar da publicação dos pacotes de recursos por conta própria.
Isso é útil quando há vários aplicativos e recursos localizados em um servidor separado.

Este modo também é adequado nos seguintes casos:

- Para usar arquivos de ativos somente com CDN.
- Para usar um construtor de módulo externo, como [webpack](https://github.com/webpack/webpack).
- Para uma única publicação, como ao implantar um aplicativo.

Outra forma é pré-publicar ativos. Para fazer isso, você precisa criar um comando de console e executá-lo no momento
da publicação dos aplicativos:

```php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;

class PublishCommand extends Command
{
    protected static $defaultName = 'assets/publish';

    private AssetManager $assetManager;

    public function __construct(AssetManager $assetManager, Aliases $aliases)
    {
        $this->assetManager = $assetManager->withPublisher(new AssetPublisher($aliases));
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {        
        $this->assetManager->registerMany([/* asset bundle names */]);
        // To register all bundles if the allowed asset bundle names are used.
        //$this->assetManager->registerAllAllowed();
        
        $output->writeln('<info>Done</info>');
        return 0;
    }
}
```

## Pré-publicação ao usar um balanceador de carga

A pré-publicação de pacotes de ativos é adequada para arquivos locais e vários servidores atrás de um balanceador de carga.
Neste último caso você precisa usar um hash estático para o nome do diretório que é criado durante a publicação.
Assim como no exemplo anterior, você precisa criar um comando de console e executá-lo no momento da
publicação dos aplicativos:

```php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;

class PublishCommand extends Command
{
    protected static $defaultName = 'assets/publish';

    private AssetManager $assetManager;

    public function __construct(AssetManager $assetManager, Aliases $aliases)
    {
        $publisher = (new AssetPublisher($aliases))
            ->withHashCallback(static fn (string $path): string => hash('md4', $path))
        ;
        $this->assetManager = $assetManager->withPublisher($publisher);
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {   
        $this->assetManager->registerMany([/* asset bundle names */]);
        // To register all bundles if the allowed asset bundle names are used.
        //$this->assetManager->registerAllAllowed();
        
        $output->writeln('<info>Done</info>');
        return 0;
    }
}
```

## Exportando pacotes de ativos

A exportação automatiza a coleta de caminhos de arquivos de pacotes de ativos para construtores de módulos externos.
Dois exportadores são fornecidos prontos para uso:

- `Yiisoft\Assets\Exporter\JsonAssetExporter` - exporta os caminhos dos arquivos dos pacotes de recursos para um arquivo JSON.
- `Yiisoft\Assets\Exporter\WebpackAssetExporter` - exporta os caminhos dos arquivos dos pacotes de ativos, convertendo-os para expressões
   `import '/path/to/file';` e colocá-las no arquivo JavaScript especificado para carregá-las posteriormente
   pelo Webpack. Para mais informações, [veja aqui](https://webpack.js.org/concepts/#entry).

A exportação é especialmente útil ao usar os nomes permitidos de pacotes de recursos:

```php
use Yiisoft\Assets\Exporter\JsonAssetExporter;
use Yiisoft\Assets\Exporter\WebpackAssetExporter;
use Yiisoft\Assets\AssetManager;

/** 
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Yiisoft\Assets\AssetLoaderInterface $loader
 */

$assetManager = new AssetManager($aliases, $loader, [
    \App\Assets\BootstrapAsset::class,
    \App\Assets\MainAsset::class,
]);

$assetManager->export(new JsonAssetExporter('/path/to/file.json'));
$assetManager->export(new WebpackAssetExporter('/path/to/file.js'));
```

Se os nomes de pacotes de ativos permitidos não forem usados, eles deverão ser registrados antes da exportação:

```php
use Yiisoft\Assets\Exporter\JsonAssetExporter;
use Yiisoft\Assets\Exporter\WebpackAssetExporter;
use Yiisoft\Assets\AssetManager;

/** 
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Yiisoft\Assets\AssetLoaderInterface $loader
 */

$assetManager = new AssetManager($aliases, $loader);

$assetManager->registerMany([
    \App\Assets\BootstrapAsset::class,
    \App\Assets\MainAsset::class,
]);

$assetManager->export(new JsonAssetExporter('/path/to/file.json'));
$assetManager->export(new WebpackAssetExporter('/path/to/file.js'));
```

Você pode criar seus próprios exportadores personalizados para diversas integrações,
você só precisa implementar `Yiisoft\Assets\AssetExporterInterface`.
