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

define('DS', DIRECTORY_SEPARATOR);
define('BASE', dirname(__FILE__));
define('ROOT', dirname(BASE));
define('INPUT', BASE . DS . 'input' . DS);
define('OUTPUT', BASE . DS . 'output' . DS);
define('AUTOLOAD', ROOT . DS . 'vendor' . DS . 'autoload.php');

require_once AUTOLOAD;
require_once BASE . DS . 'helpers.php';

$defer = new shinsenter\Defer('');
$list  = glob(INPUT . '*.html');

mem_info();

foreach ($list as $file) {
    $defer->setHtml(file_get_contents($file));

    @file_put_contents(
        OUTPUT . preg_replace('/^.+\//', '', $file),
        print_r($defer->deferHtml(), true)
    );

    $defer->reset();
    mem_info();
}
