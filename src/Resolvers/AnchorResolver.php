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

namespace AppSeeds\Resolvers;

use AppSeeds\Contracts\DeferNormalizable;

class AnchorResolver extends DeferResolver implements DeferNormalizable
{
    /*
    |--------------------------------------------------------------------------
    | DeferNormalizable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function normalize()
    {
        if ($this->node->hasAttribute('target') && !$this->node->hasAttribute('rel')) {
            // rel="noopener" prevents the new page from being able
            // to access the window.opener property
            // and ensures it runs in a separate process.
            $this->node->setAttribute('rel', 'noopener');
        }

        $href = $this->node->getAttribute('href');

        if (empty($href)) {
            $this->node->setAttribute('href', '#');
            $this->node->setAttribute('rel', 'nofollow');
        } elseif ($href == 'javascript:void(0);') {
            $this->node->setAttribute('href', 'javascript:;');
            $this->node->setAttribute('rel', 'nofollow');
        } elseif (preg_match('/^(\#|javascript)/i', $href)) {
            $this->node->setAttribute('rel', 'nofollow');
        }
    }
}
