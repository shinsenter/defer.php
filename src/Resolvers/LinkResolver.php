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
use AppSeeds\Contracts\DeferPreloadable;
use AppSeeds\Contracts\DeferReorderable;
use AppSeeds\Elements\ElementNode;
use AppSeeds\Helpers\DeferAssetUtil;
use AppSeeds\Helpers\DeferConstant;

class LinkResolver extends DeferResolver implements
    DeferNormalizable,
    DeferReorderable,
    DeferPreloadable,
    DeferMinifyable,
    DeferLazyable
{
    const STYLESHEET   = 'stylesheet';
    const PRELOAD      = 'preload';
    const PRECONNECT   = 'preconnect';
    const PREFETCH     = 'prefetch';
    const DNS_PREFETCH = 'dns-prefetch';

    public static $preload_cache = [];

    /*
    |--------------------------------------------------------------------------
    | Static functions
    |--------------------------------------------------------------------------
     */

    public static function reset()
    {
        static::$preload_cache = [];
    }

    public static function registerPreload(ElementNode &$node, $name)
    {
        if (static::registered($name)) {
            $node->detach();
        } else {
            static::$preload_cache[$name] = $node;
        }

        return static::$preload_cache[$name];
    }

    public static function registered($name)
    {
        return isset(static::$preload_cache[$name]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resolver functions
    |--------------------------------------------------------------------------
     */

    public function isCss()
    {
        return strtolower($this->node->getAttribute('rel')) == self::STYLESHEET;
    }

    public function isPreload()
    {
        return strtolower($this->node->getAttribute('rel')) == self::PRELOAD;
    }

    public function isPreconnect()
    {
        return strtolower($this->node->getAttribute('rel')) == self::PRECONNECT;
    }

    public function isPrefetch()
    {
        return strtolower($this->node->getAttribute('rel')) == self::PREFETCH;
    }

    public function isDnsPrefetch()
    {
        return strtolower($this->node->getAttribute('rel')) == self::DNS_PREFETCH;
    }

    public function name()
    {
        return implode('|', [
            $this->node->getAttribute('rel'),
            $this->node->getAttribute('href'),
            $this->node->getAttribute('charset'),
            $this->node->getAttribute('media'),
        ]);
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
        // Normalize the URL
        $href = $this->node->getAttribute('href');

        if (!empty($href)) {
            if ($this->isPreconnect() || $this->isDnsPrefetch()) {
                $normalized = DeferAssetUtil::normalizeUrlOrigin($href);
            } else {
                $normalized = DeferAssetUtil::normalizeUrl($href);
            }

            if ($href != $normalized) {
                $this->node->setAttribute('href', $normalized);
            }
        }

        // Normalize for stylesheet
        if ($this->isCss()) {
            $media = $this->resolveAttr('media', DeferConstant::UNIFY_MEDIA) ?: 'all';

            if ($media == 'all') {
                $this->node->removeAttribute('media');
            }

            $this->node->removeAttribute('type');
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
        if ($this->isCss()
            || $this->isDnsPrefetch()
            || $this->isPreconnect()
            || $this->isPreload()
            || $this->isPrefetch()) {
            $this->node->detach();

            if (empty($this->getAttribute('href'))) {
                return;
            }
        }

        // Add stylesheet tag to the bottom of the head tag
        if ($this->isCss() && $this->options->fix_render_blocking) {
            $node = $this->nodeOrNoscript();
            $this->head()->appendWith($node);
        }

        // Add preconnect, dns-prefetch tag to the top of the head tag
        elseif ($this->isPreconnect() || $this->isDnsPrefetch()) {
            $this->title()->precede($this->node);
        }

        // Add preload, prefetch tag to the top of the head tag
        elseif ($this->isPreload() || $this->isPrefetch()) {
            $this->title()->precede($this->node);
        }

        // Prevent duplicated preload
        static::registerPreload($this->node, $this->name());
    }

    /*
    |--------------------------------------------------------------------------
    | DeferPreloadable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function isThirdParty()
    {
        return DeferAssetUtil::isThirdParty(
            $this->node->getAttribute('href'),
            $this->options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPreloadNode()
    {
        if ($this->isPreload() && !$this->skipPreloading('href')) {
            return $this->node;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreconnectNode()
    {
        if ($this->isPreconnect()) {
            return $this->node;
        }

        if ($this->isCss()) {
            $preconnect = $this->newNode('link', [
                'rel'         => LinkResolver::PRECONNECT,
                'href'        => $this->node->getAttribute('href'),
                'crossorigin' => $this->node->getAttribute('crossorigin'),
            ]);

            return $preconnect;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefetchNode()
    {
        if ($this->isPrefetch() && !$this->skipPreloading('href')) {
            return $this->node;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDnsPrefetchNode()
    {
        if ($this->isDnsPrefetch()) {
            return $this->node;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | DeferLazyable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function shouldLazyload()
    {
        return parent::shouldLazyload()
            && ($this->hasLazyloadFlag() || $this->isThirdParty());
    }

    /**
     * {@inheritdoc}
     */
    public function lazyload()
    {
        // Only defer when it is a CSS node
        // and "onload" attribute is not provided
        if (!$this->isCss() ||
            $this->node->hasAttribute('onload') ||
            $this->node->hasAttribute('onerror') ||
            $this->skipLazyloading('href')) {
            return false;
        }

        // Remove lazy attributes
        $this->node->removeAttribute(DeferConstant::ATTR_DEFER);
        $this->node->removeAttribute(DeferConstant::ATTR_LAZY);

        // Convert to preload tag
        $this->node->setAttribute('rel', 'preload');
        $this->node->setAttribute('as', 'style');
        $this->node->setAttribute('onload', sprintf(DeferConstant::TEMPLATE_RESTORE_REL_ATTR, 'stylesheet'));

        return true;
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
        $this->node->removeAttribute('class');
    }
}
