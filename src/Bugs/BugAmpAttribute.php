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

namespace AppSeeds\Bugs;

use AppSeeds\Contracts\PatchInterface;

/**
 * Fix AMP attribute in HTML tag
 */
class BugAmpAttribute implements PatchInterface
{
    /**
     * {@inheritdoc}
     */
    public function before($html, $options)
    {
        $find  = implode('|', [preg_quote('&#x26A1;', '@'), '⚡', 'amp']);
        $regex = '@(<html[^>]*)(' . $find . ')([^>]*>)@iu';

        if (!empty(preg_match($regex, $html, $matches))) {
            $html = preg_replace($regex, '$1amp$3', $html);
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function after($html, $options)
    {
        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
    }
}
