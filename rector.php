<?php

use Rector\Config\RectorConfig;
use Rector\Php71\Rector\ClassConst\PublicConstantVisibilityRector;

return RectorConfig::configure()
    ->withoutParallel()
    ->withPaths(
        [
            __DIR__ . '/src',
            __DIR__ . '/Tests',
        ]
    )
    ->withImportNames(
        importNames: true,
        importDocBlockNames: true,
        importShortClasses: false,
        removeUnusedImports: true
    )
    ->withPhpSets(php81: true)
    ->withRules([
        PublicConstantVisibilityRector::class
    ]);
