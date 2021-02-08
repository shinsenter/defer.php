<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2021 AppSeeds https://appseeds.net/
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2021 AppSeeds
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @see       https://code.shin.company/defer.php/blob/master/README.md
 */

use AppSeeds\Defer;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'defer.php';

$deferjs = (new Defer())->deferjs();

// Cleanup cache
$deferjs->purgeOffline();

// Fetch new file and cache
$cache = $deferjs->makeOffline(null, 9999999999);

print_r($cache . PHP_EOL);
