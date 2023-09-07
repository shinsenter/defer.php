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

use AppSeeds\Elements\DocumentNode;
use AppSeeds\Elements\ElementNode;
use AppSeeds\Helpers\DeferAssetUtil;
use AppSeeds\Helpers\DeferConstant;
use AppSeeds\Helpers\DeferOptions;

/**
 * @mixin ElementNode
 * @mixin DocumentNode
 */
class DeferResolver
{
    /**
     * Hold the unique id.
     *
     * @var string
     */
    protected $_uid;

    /**
     * Hold the real ElementNode.
     *
     * @var ElementNode
     */
    protected $node;

    /**
     * Hold library options.
     *
     * @var DeferOptions
     */
    protected $options;

    /**
     * Attribute backups.
     *
     * @var array
     */
    protected $attr_backups = [];

    /**
     * Hold the noscript instance.
     *
     * @var ElementNode|null
     */
    protected $fallback;

    /**
     * Constructor.
     */
    public function __construct(ElementNode &$node, DeferOptions $options)
    {
        $this->node    = $node;
        $this->options = $options;
    }

    /**
     * @param string       $method
     * @param array<mixed> $parameters
     */
    public function __call($method, $parameters)
    {
        $callee = [$this->node, $method];
        if (is_callable($callee)) {
            return call_user_func_array($callee, $parameters);
        }

        /** @var DocumentNode $dom */
        $dom    = $this->node->document();
        $callee = [$dom, $method];

        if (is_callable($callee)) {
            return call_user_func_array($callee, $parameters);
        }
    }

    /**
     * |-----------------------------------------------------------------------
     * | Static functions
     * |-----------------------------------------------------------------------.
     *
     * @param mixed $node
     * @param mixed $options
     */

    /**
     * Get resolver for specific node.
     *
     * @param ElementNode  $node
     * @param DeferOptions $options
     *
     * @return self
     */
    public static function resolver(&$node, $options)
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

        return new self($node, $options);
    }

    /**
     * |-----------------------------------------------------------------------
     * | Common functions
     * |-----------------------------------------------------------------------.
     */
    public function resolveNoScript()
    {
        if ($this->fallback instanceof ElementNode) {
            return $this->fallback;
        }

        $fallback = $this->newNode('noscript');

        $clone = $this->node->cloneNode(true);
        $fallback->prependWith($clone);
        $this->fallback = $fallback;

        return $this->fallback;
    }

    /**
     * Returns the node or parent if parent is a <noscript>.
     *
     * @return ElementNode
     */
    public function nodeOrNoscript()
    {
        $parent = $this->node->parentNode;
        if (!$parent instanceof ElementNode) {
            return $this->node;
        }

        if ($parent->nodeName != 'noscript') {
            return $this->node;
        }

        return $parent;
    }

    /**
     * Check if the node should be ignored by optimizer.
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
            if ($parent->hasAttribute(DeferConstant::ATTR_IGNORE)) {
                return true;
            }

            return $parent->nodeName == 'noscript';
        }

        return false;
    }

    /**
     * Check if the node should be lazy-loaded by optimizer.
     *
     * @return bool
     */
    public function shouldLazyload()
    {
        if (!$this->options->enable_lazyloading) {
            return false;
        }

        return !$this->node->hasAttribute(DeferConstant::ATTR_NOLAZY);
    }

    /**
     * Check if the node contains "data-lazy" or "defer" attribute.
     *
     * @return bool
     */
    public function hasLazyloadFlag()
    {
        if ($this->node->hasAttribute(DeferConstant::ATTR_DEFER)) {
            return true;
        }

        return $this->node->hasAttribute(DeferConstant::ATTR_LAZY);
    }

    /**
     * Check if the node should be skipped for lazy-loading.
     *
     * @param string $attr
     *
     * @return bool
     */
    public function skipLazyloading($attr = 'src')
    {
        $blacklist = $this->options->ignore_lazyload_paths;

        if ($blacklist !== []) {
            $value = $this->node->getAttribute($attr);

            if (!empty($value)) {
                foreach ($blacklist as $keyword) {
                    if (strstr($value, $keyword) !== false) {
                        return true;
                    }
                }
            }
        }

        $blacklist = $this->options->ignore_lazyload_texts;

        if ($blacklist !== []) {
            $text = $this->node->getText();

            if (!empty($text)) {
                foreach ($blacklist as $keyword) {
                    if (strstr($text, $keyword) !== false) {
                        return true;
                    }
                }
            }
        }

        $blacklist = $this->options->ignore_lazyload_css_class;
        $blacklist = array_filter(explode(',', implode(',', $blacklist)));

        foreach ($blacklist as $class) {
            if ($this->node->hasClass($class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * |-----------------------------------------------------------------------
     * | Helper functions
     * |-----------------------------------------------------------------------.
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
     * Unify all lazy-load attributes.
     *
     * @template KAttribute of string
     *
     * @param KAttribute             $attr
     * @param array<KAttribute>|null $attributes
     *
     * @return string
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

            if ($unified !== $original) {
                $this->node->setAttribute($attr, $unified);
            }

            return $unified;
        }

        return $this->attr_backups[$attr];
    }

    /**
     * Create data-* attribute for lazy-load.
     *
     * @param string $attr
     * @param string $placeholder
     *
     * @return self
     */
    public function createDataAttr($attr, $placeholder = '')
    {
        $data_attr = 'data-' . strtolower($attr);
        $value     = $this->node->hasAttribute($attr) ? $this->node->getAttribute($attr) : '';
        if (empty($value)) {
            return $this;
        }

        if ($this->node->hasAttribute($data_attr)) {
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
            $this->node->removeClass(DeferConstant::CLASS_DEFER_LOADED);
            $this->node->addClass(DeferConstant::CLASS_DEFER_LOADING);
        }

        return $this;
    }
}
