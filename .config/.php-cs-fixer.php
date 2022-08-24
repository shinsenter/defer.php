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

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

define('WEBHOME', '/var/www/html');

$preload = @file_get_contents(__DIR__ . '/copyright.txt');
$header  = $preload ?: '@copyright AppSeeds';

$rules = [
    '@PhpCsFixer'            => true,
    'array_syntax'           => ['syntax' => 'short'],
    'braces'                 => ['allow_single_line_closure' => true],
    'concat_space'           => ['spacing' => 'one'],
    'header_comment'         => ['header' => $header, 'comment_type' => 'PHPDoc'],
    'increment_style'        => ['style' => 'post'],
    'phpdoc_summary'         => false,
    'single_quote'           => true,
    'yoda_style'             => false,
    'binary_operator_spaces' => [
        'default'   => 'single_space',
        'operators' => [
            'or' => 'align_single_space_minimal',
            '||' => 'align_single_space_minimal',
            '='  => 'align_single_space_minimal',
            '=>' => 'align_single_space_minimal',
        ],
    ],
];

$finder = Finder::create()
    ->in(WEBHOME)
    ->name('*.php')
    ->ignoreDotFiles(false)
    ->ignoreVCS(true)
;

return (new Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setLineEnding("\n")
    ->setUsingCache(false)
;
