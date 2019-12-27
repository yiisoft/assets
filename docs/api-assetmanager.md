
<p align="center">
    <h1 align="center">Api Library AssetManager</h1>
</p>

The **AssetManager** is a library that allows you to **generate the configuration of all your css and javascript assets easily**, and then generate your html code.

### setAppendTimestamp:

```php
public setAppendTimestamp (bool $value)
```

**Description:** Append a timestamp to the URL of every published asset. 

Default Value: `false` | Values: `false`, `true` | Return Values: `void`

Example:

```php
$assetManager->setAppendTimestamp(true);
```
<hr />

### setAssetMap:

```php
public setAssetMap (array $value)
```

**Description:** Mapping from source asset files (keys) to target asset files (values). 

Default Value: `[]` | Values: `array` | Return Values: `void`

Example:

```php
$assetManager->setAssetMap([
    'jquery.js' => 'https://code.jquery.com/jquery-3.4.1.js',
]);
```
<hr />

### setBasePath:

```php
public setBasePath (string|null $value)
```

**Description:** The root directory storing the published asset files. 

Default Value: `null` | Values: `string`, `null` | Return Values: `void`

Example:

```php
$assetManager->setBasePath('@basePath'); // with \Yiisoft\Aliases\Aliases
$assetManager->setBasePath(dirname(__dir__) . '/public/assets'); // with realpath
```
<hr />

### setBaseUrl:

```php
public setBaseUrl (string|null $value)
```

**Description:** The base URL through which the published asset files can be accessed. 

Default Value: `null` | Values: `string`, `null` | Return Values: `void`

Example:

```php
$assetManager->setBaseUrl('@web'); // with \Yiisoft\Aliases\Aliases
$assetManager->setBaseUrl('/'); // url public
```
<hr />

### setBundles:

```php
public setBundles (array $value)
```

**Description:** This property is provided to customize asset bundles. 

Default Value: `[]` | Values: `array` | Return Values: `void`

Example:

```php
$assetManager->setBundles(
    [
        JqueryAsset::class => [
            'sourcePath' => null, //no publish asset bundle
            'js' => [
                [
                    'https://code.jquery.com/jquery-3.4.1.js',
                    'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                    'crossorigin' => 'anonymous'
                ]
            ]
        ]
    ]
);
```
<hr />

### setConverter:

```php
public setConverter (\Yiisoft\Assets\AssetConverterInterface $value)
```

**Description:** Config AssetConverter component. 

Default Value: `empty` | Values: `AssetConverter` | Return Values: `void`

Example:

```php
$assetManager->setConverter($container->get(AssetConverterInterface::class));
```
<hr />

### setHashCallback:

```php
public setHashCallback (callable $value)
```

**Description:** A callback that will be called to produce hash for asset directory generation. 

Default Value: `empty` | Values: `callable` | Return Values: `void`

Example:

```php
$assetManager->setHashCallback(function () {
    return 'HashCallback';
});
```
<hr />

### setPublisher:

```php
public setPublisher (\Yiisoft\Assets\AssetPublisher $value)
```

**Description:** Config AssetPublisher component. 

Default Value: `empty` | Values: `AssetPublisher` | Return Values: `void`

Example:

```php
$assetManager->setPublisher($container->get(AssetPublisher::class));
```
<hr />
