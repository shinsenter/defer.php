<?php

declare(strict_types=1);

/**
 * Defer.php is a PHP library that aims to help you
 * concentrate on webpage performance optimization.
 *
 * Copyright (c) 2022, AppSeeds (https://appseeds.net/)
 *
 * PHP Version >=7.3
 *
 * @package   AppSeeds\Defer
 * @category  core_web_vitals
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright Copyright (c) 2022, AppSeeds (https://appseeds.net/)
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @example   https://code.shin.company/defer.php/blob/master/README.md
 */

use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\CodingStyle\Rector\FuncCall\ConsistentPregDelimiterRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\Php70\Rector\If_\IfToSpaceshipRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
use Rector\Set\ValueObject\DowngradeLevelSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->disableParallel();
    $rectorConfig->importNames();
    $rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon');

    // scan paths
    $rectorConfig->paths([
        '/var/www/html/defer.php',
        '/var/www/html/src/',
        '/var/www/html/tests/',
    ]);

    // skip rules
    $rectorConfig->skip([
        // paths
        '/var/www/html/src/assets/',
        '/var/www/html/tests/output/',

        // rules
        ClosureToArrowFunctionRector::class,
        CompleteDynamicPropertiesRector::class,
        IfToSpaceshipRector::class,
        RemoveAlwaysElseRector::class,
        RemoveUnusedPrivateClassConstantRector::class,
        RemoveUnusedPrivateMethodRector::class,
        RemoveUnusedPrivatePropertyRector::class,
        ReturnTypeDeclarationRector::class,
        StaticClosureRector::class,
        UnionTypesRector::class,
    ]);

    // rule sets
    $rectorConfig->sets([
        SetList::EARLY_RETURN,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        LevelSetList::UP_TO_PHP_82,
        DowngradeLevelSetList::DOWN_TO_PHP_70,
    ]);

    // extra rules
    $rectorConfig->ruleWithConfiguration(ConsistentPregDelimiterRector::class, [
        ConsistentPregDelimiterRector::DELIMITER => '/',
    ]);
};
