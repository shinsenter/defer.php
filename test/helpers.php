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

if (!function_exists('debug')) {
    /**
     * Debug
     */
    function debug()
    {
        $args = func_get_args();
        $time = (new DateTime())->format('Y-m-d H:i:s.u');
        $text = "{$time} " . (count($args) > 1 ? "\n" : '');
        $msgs = [];

        foreach ($args as $msg) {
            if (is_string($msg)) {
                $msgs[] = $msg;
            } else {
                $msgs[] = var_export($msg, true);
            }
        }

        echo($text . implode("\n", $msgs)) . "\n";
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
     * @return string
     */
    function mem_info()
    {
        $usage = memory_get_usage(true);

        debug('Mem: ' . number_format($usage));
    }
}
