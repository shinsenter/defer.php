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

namespace AppSeeds\Contracts;

use AppSeeds\Helpers\DeferOptions;

interface PatchInterface
{
    /**
     * Escape HTML before Defer::fromHtml().
     *
     * @param string       $html
     * @param DeferOptions $options
     *
     * @return string
     */
    public function before($html, $options);

    /**
     * Render HTML after Defer::toHtml().
     *
     * @param string       $html
     * @param DeferOptions $options
     *
     * @return string
     */
    public function after($html, $options);

    /**
     * Cleanup.
     */
    public function cleanup();
}
