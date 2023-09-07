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
use AppSeeds\Contracts\DeferMinifyable;
use AppSeeds\Contracts\DeferNormalizable;
use AppSeeds\Contracts\DeferReorderable;
use AppSeeds\Elements\ElementNode;
use AppSeeds\Helpers\DeferConstant;
use AppSeeds\Helpers\DeferMinifier;

final class StyleResolver extends DeferResolver implements DeferNormalizable, DeferReorderable, DeferMinifyable, DeferLazyable
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
        $media = $this->resolveAttr('media', DeferConstant::UNIFY_MEDIA) ?: 'all';

        if ($media == 'all' || $media == DeferConstant::TEMPLATE_LAZY_MEDIA_ATTR) {
            $this->node->removeAttribute('media');
            $this->node->removeAttribute('onload');
        }

        $this->node->removeAttribute('type');

        // Normalize the Node
        $this->node->normalize();
    }

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
        if ($this->options->fix_render_blocking) {
            $node = $this->nodeOrNoscript();
            $node->detach();

            $head = $this->head();

            if ($head instanceof ElementNode) {
                // @var ElementNode $head
                $head->appendWith($node);
            }
        }
    }

    /**
     * |-----------------------------------------------------------------------
     * | DeferMinifyable functions
     * |-----------------------------------------------------------------------.
     */

    /**
     * {@inheritdoc}
     */
    public function minify()
    {
        $minified = $this->node->getText();
        $css      = $minified;
        if (!empty($css)) {
            $minified = DeferMinifier::minifyCss($css);
        }

        if (empty($minified)) {
            $this->node->detach();
        } elseif ($minified !== $css) {
            $this->node->nodeValue = '';
            $this->node->setText($minified);
        }

        $this->node->removeAttribute('type');
        $this->node->removeAttribute('class');
    }

    /**
     * |-----------------------------------------------------------------------
     * | DeferLazyable functions
     * |-----------------------------------------------------------------------.
     */

    /**
     * {@inheritdoc}
     */
    public function shouldLazyload()
    {
        if (!parent::shouldLazyload()) {
            return false;
        }

        return $this->hasLazyloadFlag();
    }

    /**
     * {@inheritdoc}
     */
    public function lazyload()
    {
        // Only defer when it is a CSS node
        // and "onload" attribute is not provided
        $media = $this->node->getAttribute('media');
        $this->node->setAttribute('data-media', $media ?: 'all');

        // Lazyload the style
        $this->node->setAttribute('media', DeferConstant::TEMPLATE_LAZY_MEDIA_ATTR);
        $this->node->setAttribute(DeferConstant::ATTR_DEFER, 'style');
        $this->node->removeAttribute(DeferConstant::ATTR_LAZY);

        return true;
    }
}
