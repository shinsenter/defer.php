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

if (!function_exists('micro')) {
    /**
     * Get micro time
     * @param mixed $time
     */
    function micro($time)
    {
        return (float) ($time->getTimestamp() . $time->format('.u'));
    }
}

if (!function_exists('debug')) {
    /**
     * Debug
     */
    function debug()
    {
        static $last_time;

        $args = func_get_args();
        $now  = new DateTime();
        $time = $now->format('Y-m-d H:i:s.u');
        $diff = !$last_time ? 0 : number_format(micro($now) - micro($last_time), 3);
        $text = "{$time} {$diff} " . (count($args) > 1 ? "\n" : '');
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

        $last_time = new DateTime();
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
