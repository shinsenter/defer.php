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

use AppSeeds\Defer;
use AppSeeds\Defer\Utilities\DeferMinifier;
use Performance\Performance;

if (!defined('DEFER_PHP_DIR')) {
    define('DEFER_PHP_DIR', dirname(__DIR__));
}

include_once DEFER_PHP_DIR . '/defer.php';

defined('DEFER_TEST_DIR') || define('DEFER_TEST_DIR', DEFER_PHP_DIR . '/tests/output');
@mkdir(DEFER_TEST_DIR);

// list
$test_list = [
    'webike' => 'https://www.webike.net/sd/24654130/',
];

// initialize profiler
Performance::point('Initial');

foreach ($test_list as $name => $url) {
    Performance::point(sprintf('[%s] Fetching', $name));
    $html = trim(@file_get_contents($url) ?: '');
    $html = preg_replace('/<\?xml.*?\?>/i', '', $html);

    Performance::point(sprintf('[%s] Saving', $name));
    @file_put_contents(DEFER_TEST_DIR . '/' . $name . '.html', $html);

    Performance::point(sprintf('[%s] Parsing', $name));
    $instance = new Defer();
    $instance->add($html);

    Performance::point(sprintf('[%s] Finding tags', $name));
    $scripts = $instance->filter('script');
    $css     = $instance->filter('style');

    Performance::point(sprintf('[%s] Looping JS tags', $name));
    $scripts->each(function ($node) {
        $dom = &$node->getNode(0);
        $dom->setAttribute('type', 'deferjs');
        $content          = $dom->textContent;
        $dom->textContent = DeferMinifier::minifyJs($content);
        // dump($node->outerHtml());
    });

    Performance::point(sprintf('[%s] Looping CSS tags', $name));
    $css->each(function ($node) {
        $dom              = &$node->getNode(0);
        $dom->textContent = DeferMinifier::minifyCss($dom->textContent);
        // dump($node->outerHtml());
    });

    Performance::point(sprintf('[%s] Export HTML', $name));
    $out_html = $instance->outerHtml();

    Performance::point(sprintf('[%s] Saving HTML', $name));
    @file_put_contents(DEFER_TEST_DIR . '/' . $name . '_out.html', $out_html);

    Performance::point(sprintf('[%s] GC', $name));
    unset($html, $out_html, $instance, $scripts);
}

// show result
Performance::results();
