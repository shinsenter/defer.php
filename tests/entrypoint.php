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
    'kenh14'    => 'https://kenh14.vn/',
    'tuoitre'   => 'https://tuoitre.vn/',
    'vnexpress' => 'https://vnexpress.net/',
    'webike'    => 'https://www.webike.net/sd/24654130/',
];

foreach ($test_list as $name => $url) {
    $instance = new Defer();

    if (file_exists(DEFER_TEST_DIR . '/' . $name . '.html')) {
        $html = @file_get_contents(DEFER_TEST_DIR . '/' . $name . '.html');
    } else {
        $instance->point(sprintf('[%s] Fetching', $name));
        $html = trim(@file_get_contents($url) ?: '');
        $html = preg_replace('/<\?xml.*?\?>/i', '', $html);

        $instance->point(sprintf('[%s] Saving', $name));
        @file_put_contents(DEFER_TEST_DIR . '/' . $name . '.html', $html);
    }

    $instance->point(sprintf('[%s] Parsing', $name));
    $instance->add($html);

    $instance->point(sprintf('[%s] Finding tags', $name));
    $scripts = $instance->filter('script');
    $css     = $instance->filter('style');

    $instance->point(sprintf('[%s] Looping JS tags', $name));
    $scripts->each(function ($node) {
        $dom = &$node->getNode(0);
        $dom->setAttribute('type', 'deferjs');
        $content          = $dom->textContent;
        $dom->textContent = DeferMinifier::minifyJs($content);
    });

    $instance->point(sprintf('[%s] Looping CSS tags', $name));
    $css->each(function ($node) {
        $dom              = &$node->getNode(0);
        $dom->textContent = DeferMinifier::minifyCss($dom->textContent);
    });

    $instance->point(sprintf('[%s] Export HTML', $name));
    $out_html = $instance->outerHtml();

    $instance->point(sprintf('[%s] Saving HTML', $name));
    @file_put_contents(DEFER_TEST_DIR . '/' . $name . '_out.html', $out_html);

    $instance->results();

    unset($instance, $html, $out_html, $css, $scripts);
}
