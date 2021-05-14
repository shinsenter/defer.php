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

$header = <<<'EOF'
Defer.php aims to help you concentrate on web performance optimization.
(c) 2021 AppSeeds https://appseeds.net/

PHP Version >=5.6

@category  Web_Performance_Optimization
@package   AppSeeds
@author    Mai Nhut Tan <shin@shin.company>
@copyright 2021 AppSeeds
@license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
@link      https://code.shin.company/defer.php
@see       https://code.shin.company/defer.php/blob/master/README.md
EOF;

$rules = [
    '@Symfony'                            => true,
    '@PSR2'                               => true,
    'align_multiline_comment'             => true,
    'array_indentation'                   => true,
    'array_syntax'                        => ['syntax' => 'short'],
    'braces'                              => ['allow_single_line_closure' => true],
    'combine_consecutive_issets'          => true,
    'combine_consecutive_unsets'          => true,
    'compact_nullable_typehint'           => true,
    'concat_space'                        => ['spacing' => 'one'],
    'escape_implicit_backslashes'         => true,
    'explicit_indirect_variable'          => true,
    'explicit_string_variable'            => true,
    'fully_qualified_strict_types'        => true,
    'header_comment'                      => ['header' => $header, 'comment_type' => 'PHPDoc'],
    'heredoc_to_nowdoc'                   => true,
    'increment_style'                     => ['style' => 'post'],
    'list_syntax'                         => ['syntax' => 'long'],
    'method_argument_space'               => ['on_multiline' => 'ensure_fully_multiline'],
    'method_chaining_indentation'         => true,
    'multiline_comment_opening_closing'   => true,
    'native_function_invocation'          => false,
    'no_alternative_syntax'               => true,
    'no_blank_lines_before_namespace'     => false,
    'no_binary_string'                    => true,
    'no_empty_phpdoc'                     => true,
    'no_null_property_initialization'     => true,
    'no_superfluous_elseif'               => true,
    'no_unneeded_curly_braces'            => true,
    'no_useless_else'                     => true,
    'no_useless_return'                   => true,
    'ordered_class_elements'              => true,
    'ordered_imports'                     => true,
    'php_unit_internal_class'             => true,
    'php_unit_test_class_requires_covers' => true,
    'phpdoc_add_missing_param_annotation' => true,
    'phpdoc_order'                        => true,
    'phpdoc_separation'                   => false,
    'phpdoc_summary'                      => false,
    'phpdoc_types_order'                  => true,
    'return_assignment'                   => false,
    'semicolon_after_instruction'         => true,
    'single_line_comment_style'           => false,
    'single_quote'                        => true,
    'yoda_style'                          => false,
    'blank_line_before_statement'         => [
        'statements' => [
            'continue', 'declare', 'return', 'throw', 'try',
            'declare', 'for', 'foreach', 'goto', 'if',
        ],
    ],
    'no_extra_blank_lines' => [
        'tokens' => [
            'continue', 'extra', 'return', 'throw', 'use',
            'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block',
        ],
    ],
    'binary_operator_spaces' => [
        'default'   => 'single_space',
        'operators' => [
            '='  => 'align_single_space_minimal',
            '=>' => 'align_single_space_minimal',
        ],
    ],
];

$finder = \PhpCsFixer\Finder::create()
    ->in(__DIR__ . DIRECTORY_SEPARATOR)
    ->name('*.php')
    ->exclude('.docker')
    ->exclude('.ppm')
    ->exclude('assets')
    ->exclude('cache')
    ->exclude('patches')
    ->exclude('public')
    ->exclude('tests/v1')
    ->exclude('v1')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new \PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setLineEnding("\n")
    ->setUsingCache(false);
