<?php

/**
 * A PHP helper class to efficiently defer JavaScript for your website.
 * (c) 2019 AppSeeds https://appseeds.net/
 *
 * @package   shinsenter/defer.php
 * @since     1.0.0
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019 AppSeeds
 * @see       https://github.com/shinsenter/defer.php/blob/develop/README.md
 */

namespace shinsenter;

class Defer extends DeferInterface
{
    use DeferOptions;
    use DeferParser;
    use DeferOptimizer;

    /**
     * To store previous state of $use_errors
     *
     * @since  1.0.0
     * @var bool
     */
    protected $use_errors;
    protected $cache_manager;
    protected $deferjs_expiry = 3600; // 1 hours
    protected $http;

    /**
     * Main class constructor
     *
     * @since  1.0.0
     * @param  string $html
     * @param  array  $options
     * @param  string $charset
     * @return self
     */
    public function __construct($html = null, $options = [], $charset = null)
    {
        $this->cache_manager = new DeferCache(static::DEFERJS_CACHE, 1);

        // Set library options
        if (!empty($options)) {
            $this->__set($options);
        }

        // Parse the html, then do the optimization
        if (!empty($html)) {
            $this->fromHtml($html, $charset);
        }

        $this->http = new DeferHttpRequest();

        return $this;
    }

    /**
     * Main class destructor
     *
     * @since  1.0.0
     */
    public function __destruct()
    {
        // Cleanup variables
        $this->cleanup();
    }

    /**
     * Load HTML from string
     *
     * @since  1.0.0
     * @param  string $html
     * @param  string $charset
     * @return self
     */
    public function fromHtml($html, $charset = null)
    {
        if ($this->nodefer()) {
            $this->nodefer_html = $html;

            return $this;
        }

        if (empty($charset)) {
            $charset = \mb_detect_encoding($html) ?: 'UTF-8';
        }

        // Disable libxml errors and warnings
        $this->use_errors = \libxml_use_internal_errors($this->hide_warnings);

        // Load charset
        $this->charset = $charset;

        // Parse the HTML
        $this->parseHtml($html);

        // Set special options for AMP page
        if ($this->isAmp) {
            $this->setAmpOptions();
        }

        // Do the optimization
        $this->optimize();

        // Restore the previous value of use_errors
        \libxml_clear_errors();
        \libxml_use_internal_errors($this->use_errors);

        return $this;
    }

    /**
     * Returns optimized HTML content
     * With debug_mode = true, this only returns the optmized tags.
     *
     * @since  1.0.0
     * @return string
     */
    public function toHtml()
    {
        if ($this->nodefer()) {
            return $this->nodefer_html;
        }

        $output = '';

        if ($this->debug_mode) {
            $output = $this->debugTags();
        } else {
            $output = $this->dom->saveHtml();
        }

        $encoding = \mb_detect_encoding($output);

        if ($encoding == 'ASCII') {
            $encoding = 'HTML-ENTITIES';
        }

        if ($this->charset !== $encoding) {
            $output = \mb_convert_encoding($output, $this->charset, $encoding);
        }

        if (!empty($this->bug72288_body)) {
            $output = str_replace('<body>', $this->bug72288_body, $output);
        }

        return $output;
    }

    /*
    |--------------------------------------------------------------------------
    | Use old functions as aliases
    |--------------------------------------------------------------------------
     */

    /**
     * An alias for fromHtml()
     *
     * @since  1.0.0
     * @param  string $html
     * @param  string $charset
     * @return self
     */
    public function setHtml($html, $charset = null)
    {
        return $this->fromHtml($html, $charset);
    }

    /**
     * An alias for toHtml()
     *
     * @since  1.0.0
     * @return string
     */
    public function deferHtml()
    {
        return $this->toHtml();
    }

    public function clearCache()
    {
        $this->cache_manager->clear();
    }

    /*
    |--------------------------------------------------------------------------
    | Other functions
    |--------------------------------------------------------------------------
     */

    protected function nodefer()
    {
        return (bool) $this->http->request()->get($this->no_defer_parameter);
    }

    /**
     * Returns only optimized tags with debug_mode = true
     *
     * @since  1.0.0
     * @return string
     */
    protected function debugTags()
    {
        $nodes = array_merge(
            $this->comment_cache,
            $this->dns_cache,
            $this->preload_cache,
            $this->style_cache,
            $this->script_cache,
            $this->img_cache,
            $this->iframe_cache,
            $this->bg_cache,
            []
        );

        $output = [];

        foreach ($nodes as $node) {
            $output[] = $this->dom->saveHtml($node);
        }

        return implode("\n", $output);
    }
}
