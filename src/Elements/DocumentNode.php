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

namespace AppSeeds\Elements;

use AppSeeds\Contracts\DeferMinifyable;
use AppSeeds\Contracts\DeferNormalizable;
use AppSeeds\Contracts\DeferOptimizable;
use AppSeeds\Helpers\DeferConstant;
use AppSeeds\Helpers\DeferMinifier;
use AppSeeds\Helpers\DeferOptimizer;
use AppSeeds\Helpers\DeferOptions;

final class DocumentNode extends \DOMDocument implements DeferOptimizable, DeferNormalizable, DeferMinifyable
{
    use CommonDomTraits;

    /** @var int */
    protected $libxmlOptions = 0;

    /**
     * @var bool
     */
    private $optimized = false;

    /**
     * @var ElementNode|null
     */
    private $root;

    /**
     * @var ElementNode|null
     */
    private $head;

    /**
     * @var ElementNode|null
     */
    private $body;

    /**
     * @var ElementNode|null
     */
    private $title;

    /**
     * {@inheritdoc}
     *
     * @param string $version
     * @param string $encoding
     */
    public function __construct($version = '1.0', $encoding = 'UTF-8')
    {
        parent::__construct($version, $encoding);
        $this->registerNodeClass('DOMDocument', self::class);
        $this->registerNodeClass('DOMElement', ElementNode::class);
        $this->registerNodeClass('DOMComment', CommentNode::class);
        $this->registerNodeClass('DOMText', TextNode::class);
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        @gc_collect_cycles();
    }

    /**
     * {@inheritdoc}
     *
     * @param string|null $html
     */
    public function setHtml($html)
    {
        if (!is_string($html)) {
            return $this;
        }

        if (trim($html) == '') {
            return $this;
        }

        $internalErrors  = @libxml_use_internal_errors(true);
        $disableEntities = @libxml_disable_entity_loader(true);
        parent::loadHTML($html, $this->libxmlOptions);

        @libxml_clear_errors();
        @libxml_use_internal_errors($internalErrors);
        @libxml_disable_entity_loader($disableEntities);

        return $this;
    }

    /**
     * |-----------------------------------------------------------------------
     * | Helper functions
     * |-----------------------------------------------------------------------.
     *
     * @param mixed $libxmlOptions
     */

    /**
     * Set libxml options.
     *
     * Multiple values must use bitwise OR.
     * eg: LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
     *
     * @see http://php.net/manual/en/libxml.const ants.php
     *
     * @param int $libxmlOptions
     */
    public function setLibxmlOptions($libxmlOptions)
    {
        $this->libxmlOptions = $libxmlOptions;
    }

    /**
     * Returns true if the document is an AMP page.
     *
     * @since  2.0.0
     *
     * @return bool
     */
    public function isAmpHtml()
    {
        $root = $this->root();

        return $root instanceof ElementNode && $root->hasAttribute('amp');
    }

    /**
     * Get <html> element.
     *
     * @since  2.0.0
     *
     * @return ElementNode|null
     */
    public function root()
    {
        if (!$this->root instanceof ElementNode) {
            /** @var ElementNode $root */
            $root = $this->find('html')->first();

            $this->root = $root;
        }

        return $this->root;
    }

    /**
     * Get <head> element.
     *
     * @since  2.0.0
     *
     * @return ElementNode|null
     */
    public function head()
    {
        if (!$this->head instanceof ElementNode) {
            /** @var ElementNode $head */
            $head       = $this->find('head')->first();
            $this->head = $head;
        }

        return $this->head;
    }

    /**
     * Get <body> element.
     *
     * @since  2.0.0
     *
     * @return ElementNode|null
     */
    public function body()
    {
        if (!$this->body instanceof ElementNode) {
            /** @var ElementNode $body */
            $body       = $this->find('body')->first();
            $this->body = $body;
        }

        return $this->body;
    }

    /**
     * Get <title> element.
     *
     * @since  2.0.0
     *
     * @return ElementNode|null
     */
    public function title()
    {
        if (!$this->title instanceof ElementNode) {
            /** @var ElementNode $title */
            $title       = $this->find('title')->first();
            $this->title = $title;
        }

        return $this->title;
    }

    /**
     * Create a new ElementNode.
     *
     * @since  2.0.0
     *
     * @template KAttribute of string
     * @template VAttribute of string
     *
     * @param string                                   $tag
     * @param string|array<KAttribute,VAttribute>|null $content
     * @param array<KAttribute,VAttribute>             $attributes
     *
     * @return ElementNode
     */
    public function newNode($tag, $content = null, $attributes = [])
    {
        if (is_array($content)) {
            $attributes = $content;
            $content    = null;
        }

        $tag = strtolower($tag);

        if ($tag == 'script' && !empty($content)) {
            $content = htmlspecialchars($content);
        }

        /** @var ElementNode $node */
        $node = $this->createElement($tag, $content ?: '');

        if ($attributes !== []) {
            foreach (array_filter($attributes) as $key => $value) {
                $node->setAttribute($key, $value);
            }
        }

        return $node;
    }

    /**
     * Add missing meta tags.
     *
     * @since  2.0.0
     *
     * @return self
     */
    public function addMissingMeta()
    {
        /** @var ElementNode $html */
        $html = $this->root();

        /** @var ElementNode $head */
        $head = $this->head();

        // Add missing <meta http-equiv="X-UA-Compatible"> tag
        /** @var ElementNode|null $meta_compatible */
        $meta_compatible = $html->find('meta[http-equiv="X-UA-Compatible"]')->first();

        if ($meta_compatible == null) {
            $meta_compatible = $this->newNode('meta', [
                'http-equiv' => 'X-UA-Compatible',
                'content'    => 'IE=edge',
            ]);
        } else {
            $meta_compatible->detach();
        }

        $head->prependWith($meta_compatible);

        // Add missing <meta name="viewport"> tag
        /** @var ElementNode|null $meta_viewport */
        $meta_viewport = $html->find('meta[name="viewport"]')->first();

        if ($meta_viewport == null) {
            $meta_viewport = $this->newNode('meta', [
                'name'    => 'viewport',
                'content' => 'width=device-width,initial-scale=1',
            ]);
        } else {
            $meta_viewport->detach();
        }

        $head->prependWith($meta_viewport);

        // Add missing <meta charset=""> tag
        /** @var ElementNode|null $meta_charset */
        $meta_charset = $html->find('meta[charset],meta[http-equiv="Content-Type"]')->first();

        if ($meta_charset == null) {
            $meta_charset = $this->newNode('meta', [
                'charset' => $this->encoding ?: 'utf-8',
            ]);
        } else {
            $meta_charset->detach();
        }

        $head->prependWith($meta_charset);

        return $this;
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
        // Common elements
        $html = $this->root();

        // Add missing <head> tag
        if ($html instanceof ElementNode && $this->head() == null) {
            $html->prependWith($this->newNode('head'));
        }

        // Add missing <body> tag
        if ($html instanceof ElementNode && $this->body() == null) {
            $html->appendWith($this->newNode('body'));
        }

        $head  = $this->head();
        $title = $this->title();

        // Add missing <title> tag
        if ($title == null) {
            $title = $this->newNode('title', '');
        } else {
            $title->detach();
        }

        if ($head instanceof ElementNode) {
            $head->prependWith($title);
        }

        // Add initial class name
        if ($html instanceof ElementNode) {
            $html->addClass(DeferConstant::CLASS_NO_DEFERJS);
        }

        // Normalize the DOM
        parent::normalize();
    }

    /**
     * |-----------------------------------------------------------------------
     * | DeferOptimizable functions
     * |-----------------------------------------------------------------------.
     *
     * @param mixed $options
     */

    /**
     * {@inheritdoc}
     *
     * @param DeferOptions $options
     */
    public function optimize($options)
    {
        if (!$this->optimized) {
            // Call DeferOptimizer
            DeferOptimizer::optimizeDocument($this, $options);

            // Update optimized flag
            $this->optimized = true;
        }

        return $this;
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
        DeferMinifier::minifyDom($this);
    }
}
