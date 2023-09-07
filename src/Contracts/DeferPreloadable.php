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

use AppSeeds\Elements\ElementNode;

interface DeferPreloadable
{
    /**
     * Check if the resource is an third-party assets.
     *
     * @return bool
     */
    public function isThirdParty();

    /**
     * Return new <link type="preload"> node.
     *
     * @return ElementNode|null
     */
    public function getPreloadNode();

    /**
     * Return new <link type="preconnect"> node.
     *
     * @return ElementNode|null
     */
    public function getPreconnectNode();

    /**
     * Return new <link type="prefetch"> node.
     *
     * @return ElementNode|null
     */
    public function getPrefetchNode();

    /**
     * Return new <link type="dns-prefetch"> node.
     *
     * @return ElementNode|null
     */
    public function getDnsPrefetchNode();
}
