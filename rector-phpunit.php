<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\PHPUnit\Set\PHPUnitSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->disableParallel();
    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();

    $rectorConfig->paths([
        __DIR__ . '/Tests/**/*Test.php',
    ]);

    $rectorConfig->skip([
        // for tests
        '*/Source/*',
        '*/Fixture/*',
        '*/Expected/*',
        '*/Benchmark/*',
    ]);

    $rectorConfig->sets([
        PHPUnitSetList::PHPUNIT_100,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);

    $rectorConfig->ruleWithConfiguration(StringClassNameToClassConstantRector::class, [
        // keep unprefixed to protected from downgrade
        'PHPUnit\Framework\*',
        'Prophecy\Prophet',
    ]);
};
