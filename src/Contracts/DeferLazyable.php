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

interface DeferLazyable
{
    /**
     * Apply lazy-load for the element.
     */
    public function lazyload();

    /**
     * Get / generate <noscript> node.
     *
     * @return ElementNode|null
     */
    public function resolveNoScript();

    /**
     * Check if the node should be lazy-loaded by optimizer.
     *
     * @return bool
     */
    public function shouldLazyload();
}
