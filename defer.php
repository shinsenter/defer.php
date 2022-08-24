<?php

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

if (!defined('DEFER_PHP_DIR')) {
    define('DEFER_PHP_DIR', __DIR__);
}

if (!class_exists('AppSeeds\Defer')) {
    $baseDir  = DEFER_PHP_DIR;
    $super    = sprintf('%s/../../vendor/autoload.php', $baseDir);
    $autoload = sprintf('%s/vendor/autoload.php', $baseDir);

    if (file_exists($autoload)) {
        require_once $autoload;
    } elseif (file_exists($super)) {
        require_once $super;
    } else {
        throw new ErrorException('Missing dependencies. Please run `composer install`.');
    }
}
