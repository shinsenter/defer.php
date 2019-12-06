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

    protected $native_libxml;
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
        $this->native_libxml = class_exists('DOMDocument');
        $this->cache_manager = new DeferCache(static::DEFERJS_CACHE, 1);
        $this->http          = new DeferHttpRequest();

        // Set library options
        if (!empty($options)) {
            $this->__set($options);
        }

        // Parse the html, then do the optimization
        if (!empty($html)) {
            $this->fromHtml($html, $charset);
        }

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
            $this->nodefer_html = trim($html);

            return $this;
        }

        // Check if gc_enable is true
        $gc_enabled = @gc_enabled();

        // Turn on gc_enable
        if (!$gc_enabled) {
            @gc_enable();
        }

        // Disable libxml errors and warnings
        $this->use_errors = @libxml_use_internal_errors($this->hide_warnings);

        // Set the charset
        $this->charset = $charset;

        // Parse the HTML
        $this->parseHtml($html);

        // Set special options for AMP page
        if ($this->isAmp) {
            $this->backupOptions();
            $this->setAmpOptions();
        }

        // Do the optimization
        $this->optimize();
        $this->restoreOptions();

        // Restore the previous value of use_errors
        @libxml_clear_errors();
        @libxml_use_internal_errors($this->use_errors);

        // Restore original gc_enable setting
        if (!$gc_enabled) {
            @gc_disable();
        }

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

        $output = $this->script_decode($output);
        $output = $this->entity2charset($output, $this->charset);

        if (!empty($this->bug72288_body)) {
            $output = preg_replace('/(<body[^>]*>)/mi', $this->bug72288_body, $output, 1);
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

    /**
     * Clear cache
     *
     * @since  1.0.0
     * @return self
     */
    public function clearCache()
    {
        $this->cache_manager->clear();

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Other functions
    |--------------------------------------------------------------------------
     */

    /**
     * Returns TRUE if nodefer parameter presents
     *
     * @since  1.0.6
     * @return bool
     */
    protected function nodefer()
    {
        $no_libxml   = !$this->native_libxml;
        $request     = $this->http->request();
        $has_nodefer = $request
            ? (bool) $request->get($this->no_defer_parameter)
            : !empty($_REQUEST[$this->no_defer_parameter]);

        return $has_nodefer || $no_libxml;
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

        return implode("\n", array_unique($output));
    }
}
