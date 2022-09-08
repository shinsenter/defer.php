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

namespace AppSeeds\Defer\Utilities;

use Exception;
use Garfix\JsMinify\Minifier;

final class DeferMinifier
{
    /**
     * Minify JavaScript.
     *
     * @param string $input
     *
     * @return string
     */
    public static function minifyJs($input)
    {
        try {
            return Minifier::minify($input);
        } catch (Exception $exception) {
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
        $minified = preg_replace('/\s*([,\+\*\/>~;:!}{]{1})\s*/', '$1', $input);
        $minified = strtr($minified, [';}' => '}', "\r" => '', "\n" => '']);

        // Strip comments
        // See: https://gist.github.com/orangexception/1292778
        $minified = preg_replace(
            ['/\/\*(?:(?!\*\/).)*\*\//', '/([;}])\s+/'],
            ['', '$1'],
            $minified
        );

        // Fix: The + , - , * , and / operators in calc() must be surrounded by whitespaces
        $minified = preg_replace_callback('/calc\([^;}]+\)/', function ($calc) {
            return strtr($calc[0], ['+' => ' + ', '*' => ' * ', '/' => ' / ']);
        }, $minified);

        return trim($minified);
    }

    /**
     * Minify JSON source.
     *
     * @param string     $input
     * @param mixed|null $flag
     *
     * @return string
     */
    public static function minifyJson($input, $flag = null)
    {
        if (is_string($input) && !empty($input)) {
            $input = trim($input);
            $obj   = json_decode($input, true);
            $flag  = $flag ?: (JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return empty($obj) ? $input : json_encode($obj, $flag);
        }

        return '';
    }
}
