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

define('DS', DIRECTORY_SEPARATOR);
define('BASE', dirname(__FILE__));
define('ROOT', dirname(BASE));
define('INPUT', BASE . DS . 'input' . DS);
define('OUTPUT', BASE . DS . 'output' . DS);
define('AUTOLOAD', ROOT . DS . 'defer.php');

require_once AUTOLOAD;
require_once BASE . DS . 'helpers.php';

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

$defer->defer_web_fonts       = true;
$defer->use_color_placeholder = true;

// Scan test files
$list = glob(INPUT . '*.html');

// Ready
mem_info();

foreach ($list as $file) {
    $results = $defer->fromHtml(file_get_contents($file))->toHtml();
    @file_put_contents(OUTPUT . preg_replace('/^.+\//', '', $file), $results);
    $results = null;
    $defer->cleanup();
    mem_info(preg_replace('/^.*\//', '', $file));
}
