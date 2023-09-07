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

namespace AppSeeds\Elements;

use AppSeeds\Contracts\DeferOptimizable;
use AppSeeds\Helpers\DeferOptions;

final class CommentNode extends \DOMComment implements DeferOptimizable
{
    use CommonDomTraits;

    /**
     * |-----------------------------------------------------------------------
     * | DeferOptimizable functions
     * |-----------------------------------------------------------------------.
     *
     * @param mixed $options
     */

    /**
     * {@inheritdoc}
     *
     * @param DeferOptions $options
     */
    public function optimize($options)
    {
        $this->detach();

        return $this;
    }
}
