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

if (!function_exists('debug')) {
    /**
     * Debug.
     *
     * @param array<mixed> $args
     */
    function debug(...$args)
    {
        static $last_time;

        $now  = date('Y-m-d H:i:s');
        $diff = $last_time ? number_format(microtime(true) - $last_time, 3) : 0;
        $text = sprintf('%s %s ', $now, $diff) . (count($args) > 1 ? "\n" : '');
        $msgs = array_map(static function ($msg) {
            return print_r($msg, true);
        }, (array) $args);

        if ($msgs !== []) {
            echo($text . implode("\n", $msgs)) . "\n";
        }

        $last_time = microtime(true);
    }
}

if (!function_exists('dd')) {
    /**
     * Debug and die.
     *
     * @return never
     */
    function dd()
    {
        call_user_func_array('debug', func_get_args());

        exit(1);
    }
}

if (!function_exists('mem_info')) {
    /**
     * Debug memory info.
     *
     * @param mixed|null $msg
     */
    function mem_info($msg = null)
    {
        $usage = memory_get_usage(true);

        debug('Mem: ' . number_format($usage) . ($msg ? ' - ' . $msg : ''));
    }
}
