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

if (!function_exists('debug')) {
    /**
     * Debug
     */
    function debug()
    {
        static $last_time;

        $args = func_get_args();
        $now  = date('Y-m-d H:i:s');
        $diff = !$last_time ? 0 : number_format(microtime(true) - $last_time, 3);
        $text = "{$now} {$diff} " . (count($args) > 1 ? "\n" : '');
        $msgs = [];

        foreach ($args as $msg) {
            if (is_string($msg)) {
                $msgs[] = $msg;
            } else {
                $msgs[] = print_r($msg, true);
            }
        }

        if (!empty($msgs)) {
            echo ($text . implode("\n", $msgs)) . "\n";
        }

        $last_time = microtime(true);
    }
}

if (!function_exists('dd')) {
    /**
     * Debug and die
     */
    function dd()
    {
        call_user_func_array('debug', func_get_args());
        exit(1);
    }
}

if (!function_exists('mem_info')) {
    /**
     * Debug memory info
     *
     * @param  null|mixed $msg
     * @return string
     */
    function mem_info($msg = null)
    {
        $usage = memory_get_usage(true);

        debug('Mem: ' . number_format($usage) . ($msg ? ' - ' . $msg : ''));
    }
}
