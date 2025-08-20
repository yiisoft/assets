# Yii Assets Change Log

## 5.1.1 August 20, 2025

- Enh #163: Adapt summary data in debug collector (@rustamwin)
- Enh #164: Simplify types in `AssetBundle` (@vjik)

## 5.1.0 March 08, 2025

- Chg #155: Improve static analyze annotations (@vjik)
- Chg #156: Change PHP constraint in `composer.json` to `~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0` (@vjik)
- Enh #155: Remove unnecessary `array_filter` call in `AssetUtil::extractFilePathsForExport()` method (@vjik)
- Bug #156: Fix the nullable parameter declarations for compatibility with PHP 8.4 (@vjik)

## 5.0.0 December 11, 2024

- New #150: Add `AssetManager::getUrl()` method instead of `getAssetUrl()` method that is marked as deprecated (@vjik)
- Chg #132: Move `di-web` configuration to `di` and allow to set publisher via parameters (@Gerych1984)
- Enh #119, #129: Add debug collector for `yiisoft/yii-debug` (@xepozz, @vjik)
- Enh #148: Raise the minimum PHP version to 8.1 and minor refactoring (@vjik)
- Enh #149: Improve `AssetBundle` properties' Psalm types (@vjik)
- Bug #123: `AssetManager` load empty `AssetBundle` when register wrong namespace bundle (@terabytesoftw)

## 4.0.0 February 13, 2023

- Chg #115: Adapt configuration group names to Yii conventions (@vjik)
- Enh #116: Add support of `yiisoft/aliases` version `^3.0` (@vjik)

## 3.0.0 January 26, 2023

- Enh #101: Refactoring with PHP 8 features usage (@xepozz, @vjik)
- Enh #113: Add `Yiisoft\Assets\AssetManager::registerCustomized()` (@terabytesoftw)

## 2.1.0 July 18, 2022

- Chg #91: Change path hash logic to `filemtime + filecount` (@Gerych1984)
- Chg #94: Raise the minimum PHP version to 8.0 (@Gerych1984)

## 2.0.1 June 15, 2022

- Enh #96: Add support for `2.0`, `3.0` versions of `psr/log` (@rustamwin)

## 2.0.0 November 04, 2021

- New #88: Make `Yiisoft\Assets\AssetManager::register()` accept a single class, add
  `Yiisoft\Assets\AssetManager::registerMany()` (@devanych)

## 1.0.1 August 30, 2021

- Chg #86: Use definitions from `yiisoft/definitions` in configuration (@vjik)

## 1.0.0 May 16, 2021

- Initial release.
