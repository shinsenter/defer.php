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

use AppSeeds\Elements\ElementNode;
use AppSeeds\Helpers\DeferAssetUtil;
use AppSeeds\Helpers\DeferConstant;
use AppSeeds\Helpers\DeferOptions;
use DOMNode;

class DeferResolver
{
    /**
     * @property $_uid Hold the unique id
     */
    protected $_uid;

    /**
     * @property $node Hold the real DOMElement
     */
    protected $node;

    /**
     * @property $options Hold library options
     */
    protected $options;

    /**
     * @property $backup Attribute backups
     */
    protected $attr_backups = [];

    /**
     * @property $fallback Hold the noscript instance
     */
    protected $fallback;

    /**
     * Constructor
     */
    public function __construct(ElementNode &$node, DeferOptions $options)
    {
        $this->node    = $node;
        $this->options = $options;
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this->node, $method)) {
            return $this->node->{$method}(...$parameters);
        }

        $dom = $this->node->document();

        if (method_exists($dom, $method)) {
            return $dom->{$method}(...$parameters);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Static functions
    |--------------------------------------------------------------------------
     */

    /**
     * Get resolver for specific node
     *
     * @return static
     */
    public static function resolver(ElementNode &$node, DeferOptions $options)
    {
        switch ($node->nodeName) {
            case 'a':
                return new AnchorResolver($node, $options);
            case 'link':
                return new LinkResolver($node, $options);
            case 'meta':
                return new MetaResolver($node, $options);
            case 'style':
                return new StyleResolver($node, $options);
            case 'script':
                return new ScriptResolver($node, $options);
            case 'embed':
            case 'frame':
            case 'iframe':
                return new IframeResolver($node, $options);
            case 'img':
            case 'picture':
            case 'video':
            case 'audio':
            case 'source':
                return new MediaResolver($node, $options);
            case 'input':
                if (strtolower($node->getAttribute('type')) == 'image') {
                    return new MediaResolver($node, $options);
                }
                break;
            default:
                if ($node->hasAttribute('style')) {
                    return new InlineStyleResolver($node, $options);
                }
            break;
        }

        return new static($node, $options);
    }

    /*
    |--------------------------------------------------------------------------
    | Common functions
    |--------------------------------------------------------------------------
     */

    public function resolveNoScript()
    {
        if ($this->fallback) {
            return $this->fallback;
        }

        if (empty($fallback)) {
            $fallback = $this->newNode('noscript');
        }

        $clone = $this->node->cloneNode(true);
        $fallback->prependWith($clone);
        $this->fallback = $fallback;

        return $this->fallback;
    }

    /**
     * Returns the node or parent if parent is a <noscript>
     *
     * @return ElementNode
     */
    public function nodeOrNoscript()
    {
        $parent = $this->node->parentNode;

        if ($parent instanceof DOMNode && $parent->nodeName == 'noscript') {
            return $parent;
        }

        return $this->node;
    }

    /**
     * Check if the node should be ignored by optimizer
     *
     * @return bool
     */
    public function shouldIgnore()
    {
        if ($this->node->hasAttribute(DeferConstant::ATTR_IGNORE)) {
            return true;
        }

        $parent = $this->node->parentNode;

        if ($parent instanceof ElementNode) {
            return $parent->hasAttribute(DeferConstant::ATTR_IGNORE)
                || $parent->nodeName == 'noscript';
        }

        return false;
    }

    /**
     * Check if the node should be lazy-loaded by optimizer
     *
     * @return bool
     */
    public function shouldLazyload()
    {
        if ($this->options->enable_lazyloading === false) {
            return false;
        }

        if ($this->node->hasAttribute(DeferConstant::ATTR_NOLAZY)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the node contains "data-lazy" or "defer" attribute
     *
     * @return bool
     */
    public function hasLazyloadFlag()
    {
        if ($this->node->hasAttribute(DeferConstant::ATTR_DEFER)
            || $this->node->hasAttribute(DeferConstant::ATTR_LAZY)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the node should be skipped for lazy-loading
     *
     * @param  mixed $attr
     * @return bool
     */
    public function skipLazyloading($attr = 'src')
    {
        $blacklist = $this->options->ignore_lazyload_paths;

        if (!empty($blacklist)) {
            $value = $this->node->getAttribute($attr);

            if (!empty($value)) {
                foreach ($blacklist as $keyword) {
                    if (strstr($value, $keyword) !== true) {
                        return true;
                    }
                }
            }
        }

        $blacklist = $this->options->ignore_lazyload_texts;

        if (!empty($blacklist)) {
            $text = $this->node->getText();

            if (!empty($text)) {
                foreach ($blacklist as $keyword) {
                    if (strstr($text, $keyword) !== true) {
                        return true;
                    }
                }
            }
        }

        $blacklist = $this->options->ignore_lazyload_css_class;
        $blacklist = array_filter(explode(',', implode(',', $blacklist)));

        if (!empty($blacklist)) {
            foreach ($blacklist as $class) {
                if ($this->node->hasClass($class)) {
                    return true;
                }
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper functions
    |--------------------------------------------------------------------------
     */

    /**
     * @return string
     */
    public function uid()
    {
        if (empty($this->_uid)) {
            $this->_uid = uniqid($this->node->nodeName . '@');
        }

        return $this->_uid;
    }

    /**
     * Unify all lazy-load attributes
     *
     * @param  mixed    $attr
     * @param  mixed    $attributes
     * @return string[]
     */
    public function resolveAttr($attr, $attributes = [])
    {
        if (isset($this->attr_backups[$attr])) {
            return $this->node->getAttribute($attr);
        }

        $original                  = $this->node->getAttribute($attr);
        $this->attr_backups[$attr] = DeferAssetUtil::normalizeUrl($original);

        if (is_array($attributes)) {
            $unified = $original;

            foreach ($attributes as $from_attr) {
                if (is_string($from_attr) && $this->node->hasAttribute($from_attr)) {
                    $tmp = $this->node->getAttribute($from_attr);

                    if (!empty($tmp)) {
                        $unified = $tmp;
                    }

                    $this->node->removeAttribute($from_attr);
                }
            }

            if (in_array($attr, ['src', 'href']) && !empty($unified)) {
                $unified = DeferAssetUtil::normalizeUrl($unified);
            }

            if ($unified != $original) {
                $this->node->setAttribute($attr, $unified);
            }

            return $unified;
        }

        return $this->attr_backups[$attr];
    }

    /**
     * Create data-* attribute for lazy-load
     *
     * @param  string $attr
     * @param  string $placeholder
     * @return self
     */
    public function createDataAttr($attr, $placeholder = '')
    {
        $data_attr = 'data-' . strtolower($attr);

        if (!$this->node->hasAttribute($attr)) {
            $value = '';
        } else {
            $value = $this->node->getAttribute($attr);
        }

        if (empty($value) || $this->node->hasAttribute($data_attr)) {
            return $this;
        }

        if (isset($this->attr_backups[$attr])) {
            $org_attr = $this->attr_backups[$attr];

            if ($org_attr != $value && $org_attr != $placeholder) {
                $placeholder = $org_attr;
            }
        }

        if ($placeholder != $value) {
            $this->node->setAttribute($attr, $placeholder);
            $this->node->setAttribute($data_attr, $value);
            $this->node->addClass(DeferConstant::CLASS_DEFER_LOADING);
        }

        return $this;
    }
}
