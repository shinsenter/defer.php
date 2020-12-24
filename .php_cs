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

$header = <<<'EOF'
A PHP helper class to efficiently defer JavaScript for your website.
(c) 2019 AppSeeds https://appseeds.net/

@package   shinsenter/defer.php
@since     1.0.0
@author    Mai Nhut Tan <shin@shin.company>
@copyright 2019 AppSeeds
@see       https://github.com/shinsenter/defer.php/blob/develop/README.md
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
    'no_short_echo_tag'                   => true,
    'no_superfluous_elseif'               => true,
    'no_unneeded_curly_braces'            => true,
    'no_useless_else'                     => true,
    'no_useless_return'                   => true,
    'ordered_class_elements'              => true,
    'ordered_imports'                     => true,
    'php_unit_internal_class'             => true,
    'php_unit_ordered_covers'             => true,
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
    ->in(__DIR__)
    ->name('*.php')
    ->exclude('.idea')
    ->exclude('.ppm')
    ->exclude('cache')
    ->exclude('vendor')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return \PhpCsFixer\Config::create()
    ->setFinder($finder)
    ->setRules($rules)
    ->setLineEnding("\n")
    ->setUsingCache(false);
