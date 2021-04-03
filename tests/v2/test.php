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

error_reporting(E_ALL);

define('DS', DIRECTORY_SEPARATOR);
define('BASE', dirname(__DIR__));
define('ROOT', dirname(BASE));
define('INPUT', BASE . DS . 'v2' . DS . 'input' . DS);
define('OUTPUT', BASE . DS . 'v2' . DS . 'output' . DS);
define('AUTOLOAD', ROOT . DS . 'defer.php');

require_once AUTOLOAD;
require_once BASE . DS . 'helpers.php';

// Test request arguments
$_REQUEST['nodefer']    = 0;
$_REQUEST['debug']      = 0;
$_REQUEST['debug_time'] = 1;

// New instance
$defer = new AppSeeds\Defer([
    'deferjs_src' => dirname(ROOT) . '/defer.js/dist/defer_plus.min.js',

    // Library injection
    'inline_deferjs'     => true,
    'default_defer_time' => 200,

    // Page optimizations
    'add_missing_meta_tags' => true,
    'enable_preloading'     => true,
    'enable_dns_prefetch'   => true,
    'enable_lazyloading'    => true,
    'minify_output_html'    => false,

    // Tag optimizations
    'fix_render_blocking' => true,
    'optimize_css'        => true,
    'optimize_scripts'    => true,
    'optimize_images'     => true,
    'optimize_iframes'    => true,
    'optimize_background' => true,
    'optimize_fallback'   => false,

    // Web-font optimizations
    'defer_third_party' => true,

    // Content placeholders
    'use_css_fadein_effects' => false,
    'use_color_placeholder'  => false,

    // Lazyload placeholder
    'img_placeholder'    => '',
    'iframe_placeholder' => 'about:blank',

    // Splash screen
    'custom_splash_screen' => '',

    // Blacklists
    'ignore_lazyload_css_class' => [
        'zoom-lens',
    ],
    'ignore_lazyload_css_selectors' => [
        '.header_top_icon_list img',
        '.header_logo img',
        '.banner img',
        '.logo',
    ],
]);

// Debug IN/OUT paths
debug('INPUT:  ' . INPUT);
debug('OUTPUT: ' . OUTPUT);

// Test set
$list = [
    // 'filename.html' => 'https://example.com/',
];

// Scan test files
foreach ((glob(INPUT . '*.html') ?: []) as $file) {
    $name        = preg_replace('/^.*[\/\\\\]/', '', $file);
    $list[$name] = $file;
}

// Ready
mem_info();

foreach ($list as $out => $file) {
    if (file_exists(INPUT . $out)) {
        $input = file_get_contents(INPUT . $out);
    } else {
        $input = file_get_contents($file);
        @file_put_contents(INPUT . $out, $input);
    }

    debug();

    $input_len  = number_format(strlen($input));
    $output     = $defer->fromHtml($input)->toHtml();
    $output_len = number_format(strlen($output));
    $percents   = number_format(strlen($output) / (strlen($input) ?: 1) * 100, 1);
    mem_info("After: ${out} (${output_len} / ${input_len} / ${percents}%)");

    @file_put_contents(OUTPUT . $out, $output);
    unset($input, $output);

    $defer->cleanup();
}
