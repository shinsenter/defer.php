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

namespace AppSeeds\Contracts;

interface PatchInterface
{
    /**
     * Escape HTML before Defer::fromHtml()
     *
     * @param  string $html
     * @return string
     */
    public function before($html);

    /**
     * Render HTML after Defer::toHtml()
     *
     * @param  string $html
     * @return string
     */
    public function after($html);

    /**
     * Cleanup
     */
    public function cleanup();
}
