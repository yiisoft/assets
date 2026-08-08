<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // AssetCollector optionally integrates with yiisoft/yii-debug without requiring it at runtime;
    // previously whitelisted the same way in composer-require-checker.json.
    ->ignoreErrorsOnPackage('yiisoft/yii-debug', [ErrorType::DEV_DEPENDENCY_IN_PROD]);
