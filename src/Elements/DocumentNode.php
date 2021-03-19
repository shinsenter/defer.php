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

namespace AppSeeds\Elements;

use AppSeeds\Contracts\DeferMinifyable;
use AppSeeds\Contracts\DeferNormalizable;
use AppSeeds\Contracts\DeferOptimizable;
use AppSeeds\Helpers\DeferConstant;
use AppSeeds\Helpers\DeferMinifier;
use AppSeeds\Helpers\DeferOptimizer;
use AppSeeds\Helpers\DeferOptions;
use DOMDocument;

class DocumentNode extends DOMDocument implements DeferOptimizable, DeferNormalizable, DeferMinifyable
{
    use CommonDomTraits;

    /** @var int */
    protected $libxmlOptions = 0;

    /**
     * @property bool $optimized
     */
    private $optimized = false;
    private $root;
    private $head;
    private $body;
    private $title;

    /**
     * {@inheritdoc}
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
     */
    public function setHtml($html)
    {
        if (!is_string($html) || trim($html) == '') {
            return $this;
        }

        $internalErrors  = @libxml_use_internal_errors(true);
        $disableEntities = @libxml_disable_entity_loader(true);
        parent::loadHTML($html, $this->libxmlOptions);

        @libxml_use_internal_errors($internalErrors);
        @libxml_disable_entity_loader($disableEntities);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper functions
    |--------------------------------------------------------------------------
     */

    /**
     * Set libxml options.
     *
     * Multiple values must use bitwise OR.
     * eg: LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
     *
     * @see http://php.net/manual/en/libxml.constants.php
     */
    public function setLibxmlOptions(int $libxmlOptions)
    {
        $this->libxmlOptions = $libxmlOptions;
    }

    /**
     * Returns true if the document is an AMP page
     *
     * @since  2.0.0
     * @return bool
     */
    public function isAmpHtml()
    {
        return $this->root()->hasAttribute('amp');
    }

    /**
     * Get <html> element
     *
     * @since  2.0.0
     * @return null|ElementNode
     */
    public function root()
    {
        if (empty($this->root)) {
            $this->root = $this->find('html')->first();
        }

        return $this->root;
    }

    /**
     * Get <head> element
     *
     * @since  2.0.0
     * @return null|ElementNode
     */
    public function head()
    {
        if (empty($this->head)) {
            $this->head = $this->find('head')->first();
        }

        return $this->head;
    }

    /**
     * Get <body> element
     *
     * @since  2.0.0
     * @return null|ElementNode
     */
    public function body()
    {
        if (empty($this->body)) {
            $this->body = $this->find('body')->first();
        }

        return $this->body;
    }

    /**
     * Get <title> element
     *
     * @since  2.0.0
     * @return null|ElementNode
     */
    public function title()
    {
        if (empty($this->title)) {
            $this->title = $this->find('title')->first();
        }

        return $this->title;
    }

    /**
     * Create a new ElementNode
     *
     * @since  2.0.0
     * @param  string      $tag
     * @param  string      $content
     * @param  array       $attributes
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

        $node = $this->createElement($tag, $content);

        if (count($attributes)) {
            foreach (array_filter($attributes) as $key => $value) {
                $node->setAttribute($key, $value);
            }
        }

        return $node;
    }

    /**
     * Add missing meta tags
     *
     * @since  2.0.0
     * @return self
     */
    public function addMissingMeta()
    {
        $html = $this->root();
        $head = $this->head();

        // Add missing <meta http-equiv="X-UA-Compatible"> tag
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
        // Common elements
        $html = $this->root();

        // Add missing <head> tag
        if ($this->head() == null) {
            $html->prependWith($this->newNode('head'));
        }

        // Add missing <body> tag
        if ($this->body() == null) {
            $html->appendWith($this->newNode('body'));
        }

        $title = $this->title();

        // Add missing <title> tag
        if ($title == null) {
            $title = $this->newNode('title', '');
        } else {
            $title->detach();
        }

        $this->head()->prependWith($title);

        // Add initial class name
        $html->addClass(DeferConstant::CLASS_NO_DEFERJS);
    }

    /*
    |--------------------------------------------------------------------------
    | DeferOptimizable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function optimize(DeferOptions $options)
    {
        if (!$this->optimized) {
            // Call DeferOptimizer
            DeferOptimizer::optimizeDocument($this, $options);

            // Update optimized flag
            $this->optimized = true;
        }

        return $this;
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
        DeferMinifier::minifyDom($this);
    }
}
