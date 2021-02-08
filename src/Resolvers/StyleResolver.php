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

use AppSeeds\Contracts\DeferMinifyable;
use AppSeeds\Contracts\DeferNormalizable;
use AppSeeds\Contracts\DeferReorderable;
use AppSeeds\Helpers\DeferConstant;
use AppSeeds\Helpers\DeferMinifier;

class StyleResolver extends DeferResolver implements
    DeferNormalizable,
    DeferReorderable,
    DeferMinifyable
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
        $this->node->removeAttribute('type');
        $media = $this->node->getAttribute('media') ?: 'all';

        if ($media == 'all' || $media == DeferConstant::TEMPLATE_LAZY_MEDIA_ATTR) {
            $this->node->removeAttribute('media');
            $this->node->removeAttribute('onload');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DeferReorderable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function reposition()
    {
        if ($this->options->fix_render_blocking) {
            $node = $this->nodeOrNoscript();
            $node->detach();
            $this->head()->appendWith($node);
        }
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
        $minified = $css = $this->node->getText();

        if (!empty($css)) {
            $minified = DeferMinifier::minifyCss($css);
        }

        if (empty($minified)) {
            $this->node->detach();
        } elseif ($minified != $css) {
            $this->node->nodeValue = '';
            $this->node->setText($minified);
        }

        $this->node->removeAttribute('type');
        $this->node->removeAttribute('class');
    }
}
