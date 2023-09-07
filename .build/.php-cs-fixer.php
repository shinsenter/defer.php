<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2019-2023 SHIN Company https://shin.company
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019-2023 SHIN Company
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @see       https://code.shin.company/defer.php/blob/master/README.md
 */

define('WEBHOME', '/var/www/html');

$header = <<<'EOF'
Defer.php aims to help you concentrate on web performance optimization.
(c) 2019-2023 SHIN Company https://shin.company

PHP Version >=5.6

@category  Web_Performance_Optimization
@package   AppSeeds
@author    Mai Nhut Tan <shin@shin.company>
@copyright 2019-2023 SHIN Company
@license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
@link      https://code.shin.company/defer.php
@see       https://code.shin.company/defer.php/blob/master/README.md
EOF;

$rules = [
    '@PhpCsFixer'                => true,
    'concat_space'               => ['spacing' => 'one'],
    'empty_loop_body'            => ['style' => 'braces'],
    'header_comment'             => ['header' => $header, 'comment_type' => 'PHPDoc'],
    'increment_style'            => ['style' => 'post'],
    'no_superfluous_phpdoc_tags' => false,
    'phpdoc_summary'             => true,
    'phpdoc_to_comment'          => false,
    'phpdoc_types_order'         => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
    'self_static_accessor'       => false,
    'yoda_style'                 => false,

    'multiline_whitespace_before_semicolons' => [
        'strategy' => 'no_multi_line',
    ],

    'phpdoc_align' => ['align' => 'vertical'],

    'binary_operator_spaces' => [
        'default'   => 'single_space',
        'operators' => [
            '||'  => 'align_single_space_minimal',
            'or'  => 'align_single_space_minimal',
            '='   => 'align_single_space_minimal',
            '=>'  => 'align_single_space_minimal',
            '<=>' => 'align_single_space_minimal',
        ],
    ],

    'visibility_required' => [
        'elements' => ['method', 'property'],
    ],
];

$finder = \PhpCsFixer\Finder::create()
    ->in(dirname(__DIR__))
    ->name('*.php')
    ->exclude('_old')
    ->exclude('cache')
    ->ignoreDotFiles(false)
    ->ignoreVCS(true);

return (new \PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setLineEnding("\n")
    ->setUsingCache(false);
