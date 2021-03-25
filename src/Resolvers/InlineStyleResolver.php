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

use AppSeeds\Contracts\DeferLazyable;
use AppSeeds\Contracts\DeferMinifyable;
use AppSeeds\Contracts\DeferNormalizable;
use AppSeeds\Helpers\DeferConstant;
use AppSeeds\Helpers\DeferMinifier;

class InlineStyleResolver extends DeferResolver implements
    DeferNormalizable,
    DeferMinifyable,
    DeferLazyable
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
        $this->resolveAttr('style', DeferConstant::UNIFY_STYLE);
    }

    /*
    |--------------------------------------------------------------------------
    | DeferLazyable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function lazyload()
    {
        $lazied     = false;
        $props      = array_filter(explode(';', $this->node->getAttribute('style')));
        $safe_props = [];

        foreach ($props as $prop) {
            if (!preg_match('/url\s*\([^\)]+\)/i', $prop)) {
                $safe_props[] = trim($prop);
            }
        }

        $before = implode(';', $props);
        $after  = implode(';', $safe_props);

        if ($after != $before) {
            $this->createDataAttr('style', $after);
            $lazied = true;
        }

        return $lazied;
    }

    /*
    |--------------------------------------------------------------------------
    | DeferMinifyable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function minify()
    {
        // Minify inline styles
        foreach (['data-style', 'style'] as $attr) {
            if ($this->node->hasAttribute($attr)) {
                $minified = DeferMinifier::minifyCss($this->node->getAttribute($attr));
                $this->node->setAttrOrRemove($attr, $minified);
            }
        }
    }
}
