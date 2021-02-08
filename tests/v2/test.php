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
$_REQUEST['nodefer'] = 0;
$_REQUEST['debug']   = 0;

// New instance
$defer = new AppSeeds\Defer([
    'default_defer_time'     => 500,
    'inline_deferjs'         => true,
    'manually_add_deferjs'   => false,
    'add_missing_meta_tags'  => true,
    'enable_dns_prefetch'    => true,
    'enable_lazyloading'     => true,
    'enable_preloading'      => true,
    'minify_output_html'     => true,
    'optimize_scripts'       => true,
    'use_color_placeholder'  => true,
    'use_css_fadein_effects' => true,
    // 'custom_splash_screen'   => '<p>Loading</p>',
]);

// Debug IN/OUT paths
debug('INPUT:  ' . INPUT);
debug('OUTPUT: ' . OUTPUT);
// Test set
$list = [
    'bike_detail_pc.html'  => 'https://moto.webike.net/bike_detail/1538248/?nodefer=1&ua=pc',
    'bike_detail_sp.html'  => 'https://moto.webike.net/bike_detail/1538248/?nodefer=1&ua=sp',
    'detail_pc.html'       => 'https://www.webike.net/sd/24008665/?nodefer=1&ua=pc',
    'detail_sp.html'       => 'https://www.webike.net/sd/24008665/?nodefer=1&ua=sp',
    'moto_amp.html'        => 'https://moto.webike.net/SUZUKI/0_50/STREET_MAGIC/amp/',
    'moto_report_amp.html' => 'https://moto.webike.net/moto_report/amp/',
    'moto_report.html'     => 'https://moto.webike.net/moto_report/?nodefer=1',
    'news.html'            => 'https://news.webike.net/2021/01/21/195689/?nodefer=1',
    'shop_navi_pc.html'    => 'https://moto.webike.net/shop-navi/shop/17619/?nodefer=1&ua=pc',
    'shop_navi_sp.html'    => 'https://moto.webike.net/shop-navi/shop/17619/?nodefer=1&ua=sp',
    'summary_pc.html'      => 'https://moto.webike.net/YAMAHA/251_400/XJ400/summary/?nodefer=1&ua=pc',
    'summary_sp.html'      => 'https://moto.webike.net/YAMAHA/251_400/XJ400/summary/?nodefer=1&ua=sp',
    'tab_parts_pc.html'    => 'https://www.webike.net/tab/parts/bm/1150/br/186/?nodefer=1&ua=pc',
    'tab_parts_sp.html'    => 'https://www.webike.net/tab/parts/bm/1150/br/186/?nodefer=1&ua=sp',
    'woltlab.html'         => 'https://community.woltlab.com/?nodefer=1',
    'pastelshop.html'      => 'https://pastelshop.fr/?nodefer=1',
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
    mem_info("After: ${out} (${output_len}/${input_len})");

    @file_put_contents(OUTPUT . $out, $output);
    unset($input, $output);

    $defer->cleanup();
}
