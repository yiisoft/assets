# Conversor de ativos

O objetivo do conversor de ativos é converter ativos de um formato para outro. Pronto para uso, ele suporta conversão de
vários formatos populares em JavaScript e CSS.

## Configurando a conversão de ativos

Para usar a conversão de ativos, temos que configurá-la primeiro. Vamos ver como isso é feito. Como exemplo
vamos converter [SCSS](https://sass-lang.com/) em CSS.

Usaremos [foxy](https://github.com/fxpio/foxy). Como ele chama o npm, precisaremos do [NodeJS](https://nodejs.org/en/) instalado.
Depois de feito isso, crie `package.json`:

```json
{
    "license": "BSD-3-Clause",
    "dependencies": {
        "sass": "^1.77.0"
    }
}
```

`npm install` traz três pacotes para o diretório `node_modules` de nossa aplicação.

Abaixo, estamos usando o pacote bootstrap do guia "[Pacotes de ativos](asset-bundles.md)":

```php
namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap css files.
 *
 * BootstrapAsset.
 */
final class BootstrapAsset extends AssetBundle
{
    public ?string $basePath = '@assets';
    public ?string $baseUrl = '@assetsUrl';
    public ?string $sourcePath = '@npm/bootstrap/scss';

    public array $css = [
        'bootstrap.scss',
    ];

    public array $publishOptions = [
        'only' => [
            'bootstrap.scss',
        ],
    ];

    public array $converterOptions = [
        'scss' => [
            'command' => '-I {path} --style compressed',
            'path' => '@root/tests/public/sourcepath/sass',
        ]
    ];
}
```

Como em `$css` estamos apontando para `.scss`, o gerenciador de ativos pede ao conversor de ativos para verificar se tal arquivo pode ser convertido
para CSS. Por padrão, o conversor de ativos possui definições de comando para less, scss, sass, styl, coffee e ts, mas como todos esses
devem ser instalados globalmente e temos isso como dependência local, precisamos redefinir um comando:

```php
/**
 * @var \Psr\Log\LoggerInterface $logger
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Yiisoft\Assets\AssetManager $assetManager
 */

$converter = new \Yiisoft\Assets\AssetConverter($aliases, $logger, [
    'scss' => ['css', '@npm/.bin/sass {options} {from} {to}'],
]);
$assetManager = $assetManager->withConverter($converter);
```  

ou, se feito através do contêiner `yiisoft/di`:

```php
AssetConverterInterface::class => static function (\Psr\Container\ContainerInterface $container) {
    $aliases = $container->get(\Yiisoft\Aliases\Aliases::class);
    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    return new \Yiisoft\Assets\AssetConverter($aliases, $logger, [
        'scss' => ['css', '@npm/.bin/sass {options} {from} {to}'],
    ]);
}
```

ou ainda, se feito via params.php:

```php
'yiisoft/assets' => [
    'assetConverter' => [
        'commands' => [
            'scss' => ['css', '@npm/.bin/sass {options} {from} {to}'],
        ],
        'forceConvert' => false,
    ],
],
```

`$converterOptions` do pacote de ativos define opções adicionais passadas para o utilitário de conversão. Neste caso, estamos dizendo ao `sass`
para minimizar o CSS resultante.

Agora, registrando o pacote de ativos normalmente resultaria na conversão dos ativos:

```php
$assetManager->register(\App\Assets\BootstrapAsset::class);
```
