<?php

/**
 * A PHP helper class to efficiently defer JavaScript for your website.
 * (c) 2019 AppSeeds https://appseeds.net/
 *
 * @package   shinsenter/defer.php
 * @since     1.0.0
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019 AppSeeds
 * @see       https://github.com/shinsenter/defer.php/blob/develop/README.md
 */

$baseDir   = dirname(__FILE__);
$libDir    = $baseDir . '/vendor';
$vendorDir = dirname(dirname($baseDir));
$autoload  = '/autoload.php';

if (!class_exists('shinsenter\Defer')) {
    if (file_exists($libDir . $autoload)) {
        require_once $libDir . $autoload;
    } elseif (file_exists($vendorDir . $autoload)) {
        require_once $vendorDir . $autoload;
    } else {
        require_once $baseDir . '/src/Helpers/JsMin.php';
        require_once $baseDir . '/src/DeferException.php';
        require_once $baseDir . '/src/DeferInterface.php';
        require_once $baseDir . '/src/DeferOptions.php';
        require_once $baseDir . '/src/DeferParser.php';
        require_once $baseDir . '/src/DeferOptimizer.php';
        require_once $baseDir . '/src/Defer.php';
    }
}
