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

error_reporting(E_ALL);

if (!defined('DEFER_JS_VERSION')) {
    define('DEFER_JS_VERSION', '1.1.7-c');
}

define('TEST_DS', DIRECTORY_SEPARATOR);
define('BASE', dirname(__FILE__));
define('ROOT', dirname(BASE));
define('INPUT', BASE . TEST_DS . 'input' . TEST_DS);
define('OUTPUT', BASE . TEST_DS . 'output' . TEST_DS);
define('AUTOLOAD', ROOT . TEST_DS . 'defer.php');

require_once AUTOLOAD;
require_once BASE . TEST_DS . 'helpers.php';

// $_GET['nodefer'] = true;

$defer = new shinsenter\Defer();

// Set test options
$defer->debug_mode    = false;
$defer->hide_warnings = true;

$defer->append_defer_js    = false;
$defer->default_defer_time = 10;

$defer->enable_preloading   = true;
$defer->enable_dns_prefetch = true;
$defer->fix_render_blocking = true;
$defer->minify_output_html  = true;

$defer->enable_defer_css        = true;
$defer->enable_defer_scripts    = true;
$defer->enable_defer_images     = true;
$defer->enable_defer_iframes    = true;
$defer->enable_defer_background = true;
$defer->enable_defer_fallback   = true;

$defer->defer_web_fonts        = true;
$defer->use_css_fadein_effects = true;
$defer->use_color_placeholder  = false;

// $defer->clearCache();

// Scan test files
$list = glob(INPUT . '*.html');

// Ready
// mem_info();

foreach ($list as $file) {
    $html = file_get_contents($file);

    debug();
    $results = $defer->fromHtml($html)->toHtml();
    $html    = null;
    $defer->cleanup();
    mem_info('After: ' . preg_replace('/^.*\//', '', $file));

    @file_put_contents(OUTPUT . preg_replace('/^.+\//', '', $file), $results);
    $results = null;
}
