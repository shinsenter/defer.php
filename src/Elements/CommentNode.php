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

namespace AppSeeds\Elements;

use AppSeeds\Contracts\DeferOptimizable;
use AppSeeds\Helpers\DeferOptions;
use DOMComment;

class CommentNode extends DOMComment implements DeferOptimizable
{
    use CommonDomTraits;

    /*
    |--------------------------------------------------------------------------
    | DeferOptimizable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function optimize(DeferOptions $options)
    {
        $this->detach();

        return $this;
    }
}
