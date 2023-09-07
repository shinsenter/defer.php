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

use AppSeeds\Contracts\DeferLazyable;
use AppSeeds\Contracts\DeferNormalizable;
use AppSeeds\Helpers\DeferAssetUtil;
use AppSeeds\Helpers\DeferConstant;

final class IframeResolver extends DeferResolver implements DeferNormalizable, DeferLazyable
{
    /**
     * |-----------------------------------------------------------------------
     * | DeferNormalizable functions
     * |-----------------------------------------------------------------------.
     */
    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function normalize()
    {
        $src = $this->resolveAttr('src', DeferConstant::UNIFY_SRC);

        if (!empty($src)) {
            $normalized = DeferAssetUtil::normalizeUrl($src);

            if ($normalized !== $src) {
                $this->node->setAttribute('src', $normalized);
            }
        }

        if (empty($this->node->getAttribute('title'))) {
            $this->node->setAttribute('title', basename($src ?: 'blank'));
        }

        // Browser-level image lazy-loading for the web
        if (!$this->node->hasAttribute(DeferConstant::ATTR_LOADING)) {
            $this->node->setAttribute(DeferConstant::ATTR_LOADING, 'lazy');
        }

        // Normalize the Node
        $this->node->normalize();
    }

    /**
     * |-----------------------------------------------------------------------
     * | DeferLazyable functions
     * |-----------------------------------------------------------------------.
     */

    /**
     * {@inheritdoc}
     */
    public function lazyload()
    {
        $lazied = false;

        // Create lazyload attributes
        if (strstr($this->node->getAttribute('src'), 'about:') === false
         && !$this->skipLazyloading('src')) {
            $this->createDataAttr('src', $this->options->iframe_placeholder);
            $lazied = true;
        }

        return $lazied;
    }
}
