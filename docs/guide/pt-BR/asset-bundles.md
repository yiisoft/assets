# Pacotes de ativos

O pacote de ativos representa uma coleção de arquivos de ativos, como CSS, JavaScript, imagens junto com opções de publicação.

Cada pacote de ativos possui um nome exclusivo que o identifica globalmente entre todos os pacotes de ativos usados em um aplicativo.
O nome é o [nome da classe totalmente qualificado](https://php.net/manual/en/language.namespaces.rules.php) da classe
representando isso.

Um pacote de ativos pode depender de outros pacotes de ativos. Ao registrar um pacote de ativos para um gestor de ativos,
todos os seus pacotes de ativos dependentes serão registrados recursivamente.

Existem três tipos de pacotes de ativos:

- Quando a pasta de ativos não está acessível a partir de `$baseUrl` usamos a opção `$sourcePath`
- Quando temos a pasta acessível no diretório público usamos a opção `$basePath`
- Quando queremos usar apenas CDN, definimos a opção `$cdn` do nosso AssetBundle como `true`.

Combinações destes tipos também podem ser usados.

## Definindo um ativo

Para definir seu próprio ativo, crie uma classe que se estenda de `Asset` e defina qualquer uma das opções abaixo
como propriedades públicas:

   Nome             | Tipo          | Padrão    | Descrição
------------------- |---------------|-----------|-------------
`$basePath`         |`string\|null` | `null`    | O diretório público da web que contém os arquivos de ativos neste pacote configurável.
`$baseUrl`          |`string\|null` | `null`    | A URL base para os arquivos de ativos listados em `$js` e `$css`.
`$cdn`              |`bool`         | `false`   | Indica se usaremos exclusivamente CDN.
`$css`              |`array`        | `[]`      | Lista de arquivos CSS que este pacote contém.
`$cssOptions`       |`array`        | `[]`      | As opções que serão passadas para `\Yiisoft\View\WebView::setCssFiles()`.
`$cssStrings`       |`array`        | `[]`      | Lista de blocos CSS.
`$cssPosition`      |`int\|null`    | `null`    | Especifica onde a tag `<style>` deve ser inserida em uma página.
`$converterOptions` |`array`        | `[]`      | As opções de linha de comando para o conversor.
`$depends`          |`array`        | `[]`      | Lista de nomes de classes de pacotes configuráveis dos quais este pacote configurável depende.
`$js`               |`array`        | `[]`      | Lista de arquivos JavaScript que este pacote contém.
`$jsStrings`        |`array`        | `[]`      | Lista de blocos JavaScript.
`$jsOptions`        |`array`        | `[]`      | As opções que serão passadas para `\Yiisoft\View\WebView::setJsFiles()`.
`$jsPosition`       |`int\|null`    | `null`    | Especifica onde a tag `<style>` deve ser inserida em uma página.
`$jsVars`           |`array`        | `[]`      | Variáveis JavaScript.
`$publishOptions`   |`array`        | `[]`      | As opções a serem passadas para `\Yiisoft\Assets\AssetPublisher::publish()` quando o pacote de ativos estiver sendo publicado.
`$export`           |`array`        | `[]`      | Lista de caminhos de arquivos para exportar em um formato legível por ferramentas de terceiros, como [Webpack](https://webpack.js.org/). Se o array estiver vazio, os caminhos dos arquivos `$css` e `$js` serão exportados.
`$sourcePath`       |`string\|null` | `null`    | O diretório que contém os arquivos de ativos de origem para esse pacote de ativos.

### Posições JS/CSS para [`yiisoft/view`](https://github.com/yiisoft/view)

Quando este pacote é usado com `yiisoft/view`, os valores possíveis de `$jsPosition` são:

- `\Yiisoft\View\WebView::POSITION_HEAD` - na seção head. Este é o valor padrão
   para variáveis JavaScript.
- `\Yiisoft\View\WebView::POSITION_BEGIN` - no início da seção do corpo.
- `\Yiisoft\View\WebView::POSITION_END` - no final da seção do corpo. Este é o valor padrão
   para arquivos e blocos JavaScript.
- `\Yiisoft\View\WebView::POSITION_READY` - no final da seção body (somente para strings JavaScript e
   variáveis). Isso significa que o bloco de código JavaScript será executado quando a composição do documento HTML estiver pronta.
- `\Yiisoft\View\WebView::POSITION_LOAD` - no final da seção body (somente para strings JavaScript e
    variáveis). Isso significa que o bloco de código JavaScript será executado quando a página HTML estiver completamente carregada.

Os valores possíveis de `$cssPosition` são:

- `\Yiisoft\View\WebView::POSITION_HEAD` - na seção head. Este é o valor padrão.
- `\Yiisoft\View\WebView::POSITION_BEGIN` - no início da seção do corpo.
- `\Yiisoft\View\WebView::POSITION_END` - no final da seção do corpo.

## Definindo pacote de ativos para ativos locais

Um caso de uso comum é definir pacotes configuráveis para arquivos locais. Como exemplo, criaremos um pacote com `sourcePath` apontando
para [Bootstrap 4](https://getbootstrap.com/).

Usaremos [foxy](https://github.com/fxpio/foxy). Ele chama o npm, então precisaremos do [NodeJS](https://nodejs.org/en/) instalado.
Depois de feito isso, crie `package.json`:

```json
{
    "license": "BSD-3-Clause",
    "dependencies": {
        "bootstrap": "^4.1.3",
        "jquery": "^3.3.1",
        "popper.js": "^1.14.5"
    }
}
```

O npm instala três pacotes no diretório `node_modules` da nossa aplicação. Estes são bootstrap4 e suas duas
dependências: jQuery e popper.

Adicione o alias à configuração do aplicativo em `config/params.php`:

```php
return [
    'yiisoft/aliases' => [
        'aliases' => [
            '@root' => dirname(__DIR__),
            '@public' => '@root/public',
            '@assets' => '@public/assets',
            '@assetsUrl' => '/assets',
            '@npm' => '@root/node_modules' // <-- this
        ],
    ],
];
```

Agora crie classes de pacotes de ativos. Primeiro, `BoostrapAsset.php`:

```php
namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap css/js files.
 *
 * BootstrapAsset.
 *
 * @package Bootstrap4
 */
class BootstrapAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/bootstrap/dist';

    public array $css = [
        'css/bootstrap.css',
    ];

    public array $js = [
        'js/bootstrap.js',
    ];

    public array $depends = [
        \App\Assets\JqueryAsset::class,
        \App\Assets\PopperAsset::class,
    ];

    public array $publishOptions = [
        'only' => [
            'css/bootstrap.css',
            'js/bootstrap.js',
        ]
    ];
}
```

`JqueryAsset.php`:

```php
namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap.
 *
 * JqueryAsset.
 *
 * @package Bootstrap4
 */
class JqueryAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/jquery';

    public array $js = [
        'dist/jquery.js',
    ];

    public array $publishOptions = [
        'only' => [
            'dist/jquery.js',
        ]
    ];
}
```

`PopperAsset.php`:

```php
namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap.
 *
 * PopperAsset.
 *
 * @package Bootstrap4
 */
class PopperAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/popper.js/dist';

    public array $js = [
        'umd/popper.js',
    ];

    public array $publishOptions = [
        'only' => [
            'umd/popper.js',
        ],
    ];
}
```

Observe como as dependências são especificadas.

Agora configure o gerenciador de ativos para disponibilizá-la em todas as visualizações. Isso normalmente é feito via `ViewFactory.php`:

```php
namespace App\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\Theme;
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
        return $webView->withDefaultParameters([
            'aliases' => $aliases,
            'assetManager' => $container->get(AssetManager::class), // <-- here
            'urlGenerator' => $container->get(UrlGeneratorInterface::class),
        ]);
    }
}
```

Se quisermos o bootstrap 4 em todas as nossas visualizações, simplesmente registramos ele no gerenciador de ativos no layout:

```php
// register an asset
$assetManager->register(\App\Assets\BootstrapAsset::class);

// resolve files and pass them to view
$this->setCssFiles($assetManager->getCssFiles());
$this->setJsFiles($assetManager->getJsFiles());
```

> Nota: Se você precisar registrar um ativo em uma única visualização, o registro do ativo será feito nessa visualização enquanto
a resolução de arquivos permanece no layout.

### Substituir caminhos de arquivo para exportação

Por padrão, todos os caminhos de arquivos CSS e JavaScript são exportados do pacote de recursos, mas
você pode especificar explicitamente a lista de caminhos de arquivos exportados na propriedade `$export`.

```php
namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

class AppAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@resources/assets';
    
    public array $css = [
        'css/style.css',
    ];

    public array $js = [
        'js/script.js',
    ];

    public array $export = [
        'img/image.png',
        'js/script.js',
    ];
}

Neste exemplo, os caminhos para os arquivos `img/image.png` e `js/script.js` serão exportados,
mas o caminho para o arquivo `css/style.css` não será exportado.
