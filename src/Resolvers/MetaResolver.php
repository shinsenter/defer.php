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

namespace AppSeeds\Resolvers;

use AppSeeds\Contracts\DeferReorderable;
use AppSeeds\Elements\ElementNode;

final class MetaResolver extends DeferResolver implements DeferReorderable
{
    /**
     * |-----------------------------------------------------------------------
     * | DeferReorderable functions
     * |-----------------------------------------------------------------------.
     */

    /**
     * {@inheritdoc}
     */
    public function reposition()
    {
        $this->node->detach();
        $head = $this->head();

        if ($head instanceof ElementNode) {
            $head->appendWith($this->node);
        }
    }
}
