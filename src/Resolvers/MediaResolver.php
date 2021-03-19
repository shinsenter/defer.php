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
use AppSeeds\Contracts\DeferNormalizable;
use AppSeeds\Helpers\DeferAssetUtil;
use AppSeeds\Helpers\DeferConstant;
use DOMNode;

class MediaResolver extends DeferResolver implements
    DeferNormalizable,
    DeferLazyable
{
    /*
    |--------------------------------------------------------------------------
    | Resolver functions
    |--------------------------------------------------------------------------
     */

    public function isImg()
    {
        return $this->node->nodeName == 'img';
    }

    public function isPicture()
    {
        return $this->node->nodeName == 'picture';
    }

    public function isAudio()
    {
        return $this->node->nodeName == 'audio';
    }

    public function isVideo()
    {
        return $this->node->nodeName == 'video';
    }

    public function isSource()
    {
        return $this->node->nodeName == 'source';
    }

    public function isMediaChild()
    {
        $parent = $this->node->parentNode;

        if (!($parent instanceof DOMNode)
            || (!$this->isSource() && !$this->isImg())) {
            return false;
        }

        return in_array($parent->nodeName, ['picture', 'audio', 'video']);
    }

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
        $this->resolveAttr('srcset', DeferConstant::UNIFY_SRCSET);
        $this->resolveAttr('sizes', DeferConstant::UNIFY_SIZES);
        $src = $this->resolveAttr('src', DeferConstant::UNIFY_SRC);

        if (!empty($src)) {
            $normalized = DeferAssetUtil::normalizeUrl($src);

            if ($normalized != $src) {
                $this->node->setAttribute('src', $normalized);
            }
        }

        if ($this->isImg()) {
            if (empty($this->node->getAttribute('alt'))) {
                $this->node->setAttribute('alt', basename($src ?: ''));
            }

            if (!empty($src)
                && !$this->node->hasAttribute('width')
                && !$this->node->hasAttribute('height')) {
                $size = DeferAssetUtil::getImageSizeFromUrl($src);

                if (!empty($size)) {
                    list($width, $height) = $size;

                    if ($width > 0) {
                        $this->node->setAttribute('width', $width);
                    }

                    if ($height > 0) {
                        $this->node->setAttribute('height', $height);
                    }
                }
            }
        }
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
        $lazied = false;

        // Determines whether the element is an independent node or not
        $standalone = !$this->isMediaChild();

        // Create lazyload attributes
        if (strstr($this->node->getAttribute('src'), 'data:') === false
            && !$this->skipLazyloading('src')) {
            $placeholder = '';

            if (empty($placeholder) && $this->isImg()) {
                $placeholder = $this->options->img_placeholder
                    ?: DeferAssetUtil::getSvgImage(
                        $this->node->getAttribute('width'),
                        $this->node->getAttribute('height')
                    );
            }

            $this->createDataAttr('srcset', '');
            $this->createDataAttr('src', $placeholder);

            if ($standalone) {
                $this->node->addClass(DeferConstant::CLASS_DEFER_LOADING);
            }

            // Browser-level image lazy-loading for the web
            if (!$this->node->hasAttribute(DeferConstant::ATTR_LOADING)) {
                $this->node->setAttribute(DeferConstant::ATTR_LOADING, 'lazy');
            }

            $lazied = true;
        }

        // Add color
        if ($this->isImg() && $this->options->use_color_placeholder) {
            $original = $this->node->getAttribute('style');
            $grey     = $this->options->use_color_placeholder === 'grey';
            $style    = implode(';', array_filter(array_unique([
                $original,
                DeferAssetUtil::getBgColorStyle($grey),
            ])));

            $this->node->setAttribute('style', $style);
        }

        return $lazied;
    }
}
