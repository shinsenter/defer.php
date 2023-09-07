<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2019-2023 SHIN Company https://shin.company
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019-2023 SHIN Company
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @see       https://code.shin.company/defer.php/blob/master/README.md
 */

use AppSeeds\Defer;

if (!defined('DEFER_PHP_ROOT')) {
    define('DEFER_PHP_ROOT', __DIR__);
}

if (!class_exists(Defer::class)) {
    $baseDir = DEFER_PHP_ROOT;
    $localV  = DEFER_PHP_ROOT . '/vendor/autoload.php';
    $globalV = dirname(dirname($baseDir)) . '/autoload.php';

    if (file_exists($localV)) {
        require_once $localV;
    } elseif (file_exists($globalV)) {
        require_once $globalV;
    } else {
        throw new Exception(PHP_EOL . 'Please run: php composer install' . PHP_EOL);
    }
}
