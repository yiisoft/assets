
# AssetBundles


AssetBundle represents a collection of asset files, such as CSS, JavaScript, images.

Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application. The name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php) of the class representing it.

An asset bundle can depend on other asset bundles. When registering an asset bundle with a view, all its dependent asset bundles will be automatically registered.

We can basically find 3 types of AssetBundles:

- When the asset folder is not accessible from the `'@web'` we use the `$sourcePath` option
- When we have the public directory accessible folder we use the `$basePath` option
- When we only want to use CDN we set `$cdn` option of our AssetBundle to `true`.

We can use combinations of these three types. AssetManager is flexible when it comes to managing assets.

## Defining asset

In order to define your own asset, create a class extended from `Asset` and define any of the options below.

| Name | Type | Description | Value Default |
|:----:|:----:|:-----------:|:-------------:|
|`$basePath`|`string/null`| The web public directory that contains the asset files in this bundle| `null`|
|`$baseUrl`|`string/null`| The base URL for the relative asset files listed in $js and $css.| `null`|
|`$cdn`|`bool`| Indicates if we are going to use CDN exclusively.| `false`|
|`$css`|`array`| List of css files that this bundle contains.| `[]`|
|`$cssOptions`|`array`| The options that will be passed to \Yiisoft\View\WebView::setCssFiles().| `[]`|
|`$depends`|`array`| List of bundle class names that this bundle depends on.| `[]`|
|`$js`|`array`| List of JavaScript files that this bundle contains.| `[]`|
|`$jsOptions`|`array`| The options that will be passed to \Yiisoft\View\WebView::setJsFiles().| `[]`|
|`$publishOptions`|`array`| The options to be passed to \Yiisoft\Assets\AssetPublisher::publish() when the asset bundle is being published.|`[]`|
|`$sourcePath`|`string/null`| The directory that contains the source asset files for this assetBundle.| `null`|


## 2.- Examples:

- [AssetBundle with sourcePath](/docs/examples/assetbundlewithsourcepath.md)
- [AssetBundle with basePath and depedencies using cdn](/docs/examples/assetbundlewithbasepath.md)
