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

trait DeferParser
{
    protected $charset;
    protected $isAmp;

    // DOM handler objects
    protected $dom;
    protected $xpath;
    protected $head;
    protected $body;

    // Element cache arrays
    protected $comment_cache;
    protected $dns_cache;
    protected $preload_cache;
    protected $style_cache;
    protected $script_cache;
    protected $img_cache;
    protected $iframe_cache;
    protected $bg_cache;

    // Meta tag cache arrays
    protected $dns_map;
    protected $preconnect_map;
    protected $preload_map;

    // Fix PHP bugs
    // https://bugs.php.net/bug.php?id=72288
    protected $bug72288_body;

    /**
     * Cleanup internal variables
     *
     * @since  1.0.0
     * @return self
     */
    public function cleanup()
    {
        $this->dom   = null;
        $this->xpath = null;
        $this->head  = null;
        $this->body  = null;

        $this->isAmp = false;

        $this->comment_cache = null;
        $this->dns_cache     = null;
        $this->preload_cache = null;
        $this->style_cache   = null;
        $this->script_cache  = null;
        $this->img_cache     = null;
        $this->iframe_cache  = null;
        $this->bg_cache      = null;

        $this->dns_map        = [];
        $this->preconnect_map = [];
        $this->preload_map    = [];

        // Preload polyfill
        $this->preload_map[static::POLYFILL_URL] = static::PRELOAD_SCRIPT;

        // Preload defer.js
        if (!$this->append_defer_js) {
            $this->preload_map[static::DEFERJS_URL] = static::PRELOAD_SCRIPT;
        }

        return $this;
    }

    /**
     * Parse the HTML content into DOMDocument
     *
     * @since  1.0.0
     * @param  string         $html
     * @throws DeferException
     * @return self
     */
    protected function parseHtml($html)
    {
        $this->cleanup();

        // Check if DOMDocument module was loaded
        if (!class_exists(\DOMDocument::class)) {
            throw new DeferException('DOMDocument module is not loaded. Please install php-xml module for PHP.', 1);
        }

        // Validate and load the HTML
        if (stripos($html, '</html>') === false) {
            throw new DeferException('Invalid HTML content.', 1);
        }

        // Force HTML5 doctype
        $html = preg_replace('/<!DOCTYPE html[^>]*>/i', '<!DOCTYPE html>', $html, 1);

        // Create DOM document
        $this->dom                     = new \DOMDocument();
        $this->dom->preserveWhiteSpace = false;
        $this->dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', $this->charset));

        // Create xpath object for searching tags
        $this->xpath = new \DOMXPath($this->dom);
        $this->isAmp = $this->xpath->query('//html[@amp]')->length > 0 || strpos($html, '⚡') !== false;

        // Check if the <head> tag exists
        if (($attempt = $this->xpath->query('//head')) && $attempt->length > 0) {
            $this->head = $attempt->item(0);
        }

        // Check if the <body> tag exists
        if (($attempt = $this->xpath->query('//body')) && $attempt->length > 0) {
            $this->body          = $attempt->item(0);
            $this->bug72288_body = preg_match('/(<body[^>]*>)/mi', $html, $match) ? $match[1] : '';
        }

        // If none of above, throw an error
        if (empty($this->head) || empty($this->body)) {
            throw new DeferException('Cannot parse <head> or <body> tag.', 1);
        }

        // Add fallback class name into body class
        if ($this->enable_defer_images) {
            $html            = $this->xpath->query('/html')->item(0);
            $current_class   = explode(' ', (string) $html->getAttribute('class'));
            $current_class[] = 'no-deferjs';
            $current_class   = array_filter(array_unique($current_class));
            $html->setAttribute(static::ATTR_CLASS, implode(' ', $current_class));
            $html = null;
        }

        // Parse the tags
        $this->comment_cache = $this->parseCommentTags();
        $this->dns_cache     = $this->parseDnsTags();
        $this->preload_cache = $this->parsePreloadTags();
        $this->style_cache   = $this->parseStyleTags();
        $this->script_cache  = $this->parseScriptTags();
        $this->img_cache     = $this->parseImgTags();
        $this->iframe_cache  = $this->parseIframeTags();
        $this->bg_cache      = $this->parseBackgroundTags();

        return $this;
    }

    /**
     * Parse comment blocks in the HTML
     *
     * @since  1.0.0
     * @return array
     */
    protected function parseCommentTags()
    {
        $output = [];

        foreach ($this->xpath->query(static::COMMENT_XPATH) as $node) {
            $output[] = $node;
        }

        return $output;
    }

    /**
     * Parse <link rel="dns-prefetch"> tags in the HTML
     *
     * @since  1.0.0
     * @return array
     */
    protected function parseDnsTags()
    {
        $output = [];

        foreach ($this->xpath->query(static::DNSCONN_XPATH) as $node) {
            $this->normalizeUrl($node, static::ATTR_HREF);

            $output[] = $node;
        }

        return $output;
    }

    /**
     * Parse <preload> tags in the HTML
     *
     * @since  1.0.0
     * @return array
     */
    protected function parsePreloadTags()
    {
        $output = [];

        foreach ($this->xpath->query(static::PRELOAD_XPATH) as $node) {
            $this->normalizeUrl($node, static::ATTR_HREF);

            $output[] = $node;
        }

        return $output;
    }

    /**
     * Parse <style> tags in the HTML
     *
     * @since  1.0.0
     * @return array
     */
    protected function parseStyleTags()
    {
        $output = [];

        foreach ($this->xpath->query(static::STYLE_XPATH) as $node) {
            if ($node->nodeName == static::LINK_TAG) {
                $this->normalizeUrl($node, static::ATTR_HREF);
            }

            if (stripos($node->getAttribute(static::ATTR_TYPE), 'css') !== false) {
                $node->removeAttribute(static::ATTR_TYPE);
            }

            $media = $node->getAttribute(static::ATTR_MEDIA) ?: 'all';

            if ($media == 'all') {
                $node->removeAttribute(static::ATTR_MEDIA);
            }

            $output[] = $node;
        }

        return $output;
    }

    /**
     * Parse <script> tags in the HTML
     *
     * @since  1.0.0
     * @return array
     */
    protected function parseScriptTags()
    {
        $output = [];

        foreach ($this->xpath->query(static::SCRIPT_XPATH) as $node) {
            $src = $this->normalizeUrl($node, static::ATTR_SRC);

            if (!$src) {
                $node->removeAttribute(static::ATTR_SRC);
                $node->removeAttribute(static::ATTR_DEFER);
                $node->removeAttribute(static::ATTR_ASYNC);
            }

            if (stripos($node->getAttribute(static::ATTR_TYPE), 'javascript') !== false) {
                $node->removeAttribute(static::ATTR_TYPE);
            }

            $output[] = $node;
        }

        return $output;
    }

    /**
     * Parse <img> tags in the HTML
     *
     * @since  1.0.0
     * @return array
     */
    protected function parseImgTags()
    {
        $output = [];

        if ($this->enable_defer_images) {
            foreach ($this->xpath->query(static::IMG_XPATH) as $node) {
                $this->normalizeUrl($node, static::ATTR_SRC);

                if (!$node->hasAttribute(static::ATTR_ALT)) {
                    $node->setAttribute(static::ATTR_ALT, '');
                }

                $output[] = $node;
            }
        }

        return $output;
    }

    /**
     * Parse <iframe> tags in the HTML
     *
     * @since  1.0.0
     * @return array
     */
    protected function parseIframeTags()
    {
        $output = [];

        if ($this->enable_defer_iframes) {
            foreach ($this->xpath->query(static::IFRAME_XPATH) as $node) {
                $this->normalizeUrl($node, static::ATTR_SRC);

                if (!$node->hasAttribute(static::ATTR_TITLE)) {
                    $node->setAttribute(static::ATTR_TITLE, '');
                }

                $output[] = $node;
            }
        }

        return $output;
    }

    /**
     * Parse all tags contain background image in the HTML
     *
     * @since  1.1.0
     * @return array
     */
    protected function parseBackgroundTags()
    {
        $output = [];

        if ($this->enable_defer_background) {
            foreach ($this->xpath->query(static::BACKGROUND_XPATH) as $node) {
                $output[] = $node;
            }
        }

        return $output;
    }

    /**
     * Add missing https protocol to the URL
     * Then add the URL and the domain into preload list
     *
     * @since  1.0.0
     * @param  DOMNode $node
     * @param  string  $name
     * @param  mixed   $attr
     * @return string
     */
    protected function normalizeUrl($node, $attr = 'src')
    {
        if (!empty($src = $node->getAttribute($attr))) {
            // Normalize the URL protocol
            if (preg_match('#^//#', $src)) {
                $src = 'https:' . $src;
                $node->setAttribute($attr, $src);
            }

            // Remove ads
            if (stripos($src, 'ads') !== false) {
                return;
            }

            $rel = $node->getAttribute(static::ATTR_REL);

            // Add the resouce URL to the preload list
            if (!in_array($rel, [static::REL_DNSPREFETCH, static::REL_PRECONNECT])) {
                $this->preload_map[$src] = $node;
            }

            $domain = preg_replace('/^(https?:\/\/[^\/\?]+)([\/\?]?.*)?$/', '$1', $src);

            // Add the domain to the dns list
            if (!empty($domain)) {
                $this->dns_map[$domain]        = $rel == static::REL_DNSPREFETCH ? $node : $rel;
                $this->preconnect_map[$domain] = $rel == static::REL_PRECONNECT ? $node : $rel;
            }
        }

        return $src;
    }
}
