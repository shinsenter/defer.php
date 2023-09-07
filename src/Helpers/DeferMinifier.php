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

namespace AppSeeds\Helpers;

use AppSeeds\Elements\DocumentNode;
use AppSeeds\Elements\ElementNode;
use JSMin\JSMin;

final class DeferMinifier
{
    /**
     * Minify JS source.
     *
     * @param string $input
     *
     * @return string
     */
    public static function minifyJs($input)
    {
        try {
            return JSMin::minify($input);
        } catch (\Exception $exception) {
            return trim($input);
        }
    }

    /**
     * Minify CSS source.
     *
     * @param string $input
     *
     * @return string
     */
    public static function minifyCss($input)
    {
        // Minify the css code
        // See: https://gist.github.com/clipperhouse/1201239/cad48570925a4f5ff0579b654e865db97d73bcc4
        $minified = preg_replace('/\s*([,\+\*\/>~;:!}{]{1})\s*/', '$1', $input) ?: '';
        $minified = strtr($minified, [';}' => '}', "\r" => '', "\n" => '']);

        // Strip comments
        // See: https://gist.github.com/orangexception/1292778
        $minified = preg_replace(
            ['/\/\*(?:(?!\*\/).)*\*\//', '/([;}])\s+/'],
            ['', '$1'],
            $minified
        ) ?: '';

        // Fix: The + , - , * , and / operators in calc() must be surrounded by whitespaces
        $minified = preg_replace_callback('/calc\([^;}]+\)/', static function ($calc) {
            return strtr($calc[0], ['+' => ' + ', '*' => ' * ', '/' => ' / ']);
        }, $minified) ?: '';

        return trim($minified);
    }

    /**
     * Minify JSON source.
     *
     * @param string   $input
     * @param int|null $flag
     *
     * @return string
     */
    public static function minifyJson($input, $flag = null)
    {
        if (!is_string($input)) {
            return '';
        }

        if (empty($input)) {
            return '';
        }

        try {
            $obj  = json_decode(trim($input), true);
            $flag = $flag ?: (JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return empty($obj)
                ? $input
                : (json_encode(json_decode(trim($input), true), $flag) ?: '');
        } catch (\Exception $exception) {
            return $input;
        }
    }

    /**
     * Minify DocumentNode.
     *
     * @param DocumentNode $dom
     *
     * @return DocumentNode
     */
    public static function minifyDom(&$dom)
    {
        // Detach comment nodes
        $skip_comments = [
            'not(contains(.,"[if "))',
            'not(contains(.,"[endif]"))',
            'not(contains(.,"' . DeferConstant::TXT_DEBUG . '"))',
        ];
        $dom->findXPath('//comment()[' . implode(' and ', $skip_comments) . ']')->detach();

        // Trim white space
        $root = $dom->root();
        if ($root instanceof ElementNode) {
            $root->findXPath('//text()[not(.=normalize-space(.))]')->normalize();
        }

        return $dom;
    }
}
