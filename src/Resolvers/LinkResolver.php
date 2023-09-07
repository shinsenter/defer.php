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
use AppSeeds\Contracts\DeferPreloadable;
use AppSeeds\Contracts\DeferReorderable;
use AppSeeds\Elements\ElementNode;
use AppSeeds\Helpers\DeferAssetUtil;
use AppSeeds\Helpers\DeferConstant;

final class LinkResolver extends DeferResolver implements DeferNormalizable, DeferReorderable, DeferPreloadable, DeferMinifyable, DeferLazyable
{
    /**
     * @var string
     */
    const STYLESHEET = 'stylesheet';

    /**
     * @var string
     */
    const PRELOAD = 'preload';

    /**
     * @var string
     */
    const PRECONNECT = 'preconnect';

    /**
     * @var string
     */
    const PREFETCH = 'prefetch';

    /**
     * @var string
     */
    const DNS_PREFETCH = 'dns-prefetch';

    /**
     * @var array<string,ElementNode>
     */
    public static $preload_cache = [];

    /**
     * |-----------------------------------------------------------------------
     * | Static functions
     * |-----------------------------------------------------------------------.
     */
    public static function reset()
    {
        static::$preload_cache = [];
    }

    /**
     * @param ElementNode $node
     * @param string      $name
     */
    public static function registerPreload(&$node, $name)
    {
        if (static::registered($name)) {
            $node->detach();
        } else {
            static::$preload_cache[$name] = $node;
        }

        return static::$preload_cache[$name];
    }

    /**
     * @param string $name
     */
    public static function registered($name)
    {
        return isset(static::$preload_cache[$name]);
    }

    /**
     * |-----------------------------------------------------------------------
     * | Resolver functions
     * |-----------------------------------------------------------------------.
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
        // Normalize the URL
        $href = $this->node->getAttribute('href');

        if (!empty($href)) {
            if ($this->isPreconnect() || $this->isDnsPrefetch()) {
                $normalized = DeferAssetUtil::normalizeUrlOrigin($href);
            } else {
                $normalized = DeferAssetUtil::normalizeUrl($href);
            }

            if (!empty($normalized) && $href !== $normalized) {
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
            $head = $this->head();

            if ($head instanceof ElementNode) {
                $head->appendWith($node);
            }
        }

        // Add preconnect, dns-prefetch tag to the top of the head tag
        elseif ($this->isPreconnect() || $this->isDnsPrefetch()) {
            $title = $this->title();

            if ($title instanceof ElementNode) {
                $title->precede($this->node);
            }
        }

        // Add preload, prefetch tag to the top of the head tag
        elseif ($this->isPreload() || $this->isPrefetch()) {
            $title = $this->title();

            if ($title instanceof ElementNode) {
                $title->precede($this->node);
            }
        }

        // Prevent duplicated preload
        static::registerPreload($this->node, $this->name());
    }

    /**
     * |-----------------------------------------------------------------------
     * | DeferPreloadable functions
     * |-----------------------------------------------------------------------.
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
        if (!$this->isPreload()) {
            return null;
        }

        if ($this->skipLazyloading('href')) {
            return null;
        }

        return $this->node;
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
            return $this->newNode('link', [
                'rel'         => LinkResolver::PRECONNECT,
                'href'        => $this->node->getAttribute('href'),
                'crossorigin' => $this->node->getAttribute('crossorigin'),
            ]);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefetchNode()
    {
        if (!$this->isPrefetch()) {
            return null;
        }

        if ($this->skipLazyloading('href')) {
            return null;
        }

        return $this->node;
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

        if ($this->hasLazyloadFlag()) {
            return true;
        }

        return $this->isThirdParty();
    }

    /**
     * {@inheritdoc}
     */
    public function lazyload()
    {
        // Remove lazy attributes
        $this->node->removeAttribute(DeferConstant::ATTR_DEFER);
        $this->node->removeAttribute(DeferConstant::ATTR_LAZY);
        // Only defer when it is a CSS node
        // and "onload" attribute is not provided
        if (!$this->isCss()) {
            return false;
        }

        if ($this->node->hasAttribute('onload')) {
            return false;
        }

        if ($this->node->hasAttribute('onerror')) {
            return false;
        }

        if ($this->skipLazyloading('href')) {
            return false;
        }

        // Convert to preload tag
        $this->node->setAttribute('rel', 'preload');
        $this->node->setAttribute('as', 'style');
        $this->node->setAttribute('onload', sprintf(DeferConstant::TEMPLATE_RESTORE_REL_ATTR, 'stylesheet'));

        return true;
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
        $this->node->removeAttribute('class');
    }
}
