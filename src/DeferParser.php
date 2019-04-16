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
    // For nodefer HTML
    protected $nodefer_html;

    // Document properties
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
        $this->nodefer_html = null;

        $this->dom   = new \DOMDocument();
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

        @gc_collect_cycles();

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

        // Detect charset charset
        if (empty($this->charset)) {
            $this->charset = mb_detect_encoding($html) ?: 'UTF-8';
        }

        // Convert HTML into HTML entities
        $html = $this->charset2entity($html, $this->charset);

        // Force HTML5 doctype
        $html = preg_replace('/<!DOCTYPE html[^>]*>/i', '<!DOCTYPE html>', $html, 1);
        $html = preg_replace('/<\?xml[^>]*>/i', '', $html, 1);

        // Create DOM document
        $this->dom->preserveWhiteSpace = false;
        $this->dom->loadHTML($html);

        // Create xpath object for searching tags
        $this->xpath = new \DOMXPath($this->dom);

        // Check if this is an AMP page
        $this->isAmp = $this->isAmpHtml($html);

        // Check if the <head> tag exists
        $attempt = $this->xpath->query('//head');

        if ($attempt && $attempt->length > 0) {
            $this->head = $attempt->item(0);
            $attempt    = null;
        }

        // Check if the <body> tag exists
        $attempt = $this->xpath->query('//body');

        if ($attempt && $attempt->length > 0) {
            $this->body          = $attempt->item(0);
            $this->bug72288_body = preg_match('/(<body[^>]*>)/mi', $html, $match) ? $match[1] : '';
            $attempt             = null;
        }

        // If none of above, throw an error
        if (empty($this->head) || empty($this->body)) {
            throw new DeferException('Cannot parse <head> or <body> tag.', 1);
        }

        // Add fallback class name into body class
        if ($this->enable_defer_images) {
            $document        = $this->xpath->query('/html')->item(0);
            $current_class   = explode(' ', (string) $document->getAttribute('class'));
            $current_class[] = 'no-deferjs';
            $current_class   = array_filter(array_unique($current_class));
            $document->setAttribute(static::ATTR_CLASS, implode(' ', $current_class));
            $document = null;
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

        @gc_collect_cycles();

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

            if ($node->hasAttribute(static::ATTR_TYPE) &&
                stripos($node->getAttribute(static::ATTR_TYPE), 'css') !== false) {
                $node->removeAttribute(static::ATTR_TYPE);
            }

            if ($node->hasAttribute(static::ATTR_MEDIA)) {
                $media = $node->getAttribute(static::ATTR_MEDIA) ?: 'all';

                if ($media == 'all') {
                    $node->removeAttribute(static::ATTR_MEDIA);
                }
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

            if ($node->hasAttribute(static::ATTR_TYPE) &&
                stripos($node->getAttribute(static::ATTR_TYPE), 'javascript') !== false) {
                $node->removeAttribute(static::ATTR_TYPE);
            }

            if ($node->hasAttribute(static::ATTR_LANGUAGE) &&
                stripos($node->getAttribute(static::ATTR_LANGUAGE), 'javascript') !== false) {
                $node->removeAttribute(static::ATTR_LANGUAGE);
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
     * @since  1.0.1
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
     * @param  bool    $preload_flag
     * @return string
     */
    protected function normalizeUrl($node, $attr = 'src', $preload_flag = true)
    {
        $src = $node->getAttribute($attr);

        if (!empty($src)) {
            // Normalize the URL protocol
            if (preg_match('#^\/\/#', $src)) {
                $src = 'https:' . $src;
                $node->setAttribute($attr, $src);
            }

            // Remove urls without HTTP protocol
            if ($preload_flag && stripos($src, 'http') !== 0) {
                $preload_flag = false;
            }

            // Remove ads
            if ($preload_flag && preg_match('/ads|click|googletags|publisher/i', $src)) {
                $preload_flag = false;
            }

            if ($preload_flag) {
                $rel = $node->getAttribute(static::ATTR_REL);

                // Add the resouce URL to the preload list
                if (!in_array($rel, [static::REL_DNSPREFETCH, static::REL_PRECONNECT])) {
                    $this->preload_map[$src] = $node;
                }

                $domain = preg_replace('#^(https?://[^/\?]+)([/\?]?.*)?$#', '$1', $src);

                // Add the domain to the dns list
                if (!empty($domain)) {
                    $this->dns_map[$domain]        = $rel == static::REL_DNSPREFETCH ? $node : $rel;
                    $this->preconnect_map[$domain] = $rel == static::REL_PRECONNECT ? $node : $rel;
                }
            }
        }

        return $src;
    }

    /**
     * Return TRUE if it is an AMP page
     *
     * @since  1.0.7
     * @param  string $html
     * @return bool
     */
    protected function isAmpHtml($html)
    {
        return
            $this->xpath->query('//html[@amp]')->length > 0 ||
            strpos($html, '&#x26A1;') !== false ||
            strpos($html, '⚡') !== false;
    }

    /**
     * Return TRUE if it is an AMP page
     *
     * @since  1.0.7
     * @param  string $html
     * @param  string $charset
     * @return string
     */
    protected function charset2entity($html, $charset)
    {
        return mb_convert_encoding($html, 'HTML-ENTITIES', $charset);
    }

    /**
     * Return TRUE if it is an AMP page
     *
     * @since  1.0.7
     * @param  string $html
     * @param  string $charset
     * @return string
     */
    protected function entity2charset($html, $charset)
    {
        $encoding = mb_detect_encoding($html);

        if (empty($encoding) || $encoding == 'ASCII') {
            $encoding = 'HTML-ENTITIES';
        }

        if ($this->charset !== $encoding) {
            $html = mb_convert_encoding($html, $charset, $encoding);
        }

        return $html;
    }

    /**
     * Create a new DOMNode
     *
     * @since  1.0.7
     * @param  string  $tag
     * @param  string  $content
     * @param  array   $attributes
     * @return DOMNode
     */
    protected function createNode($tag, $content = null, $attributes = [])
    {
        if (is_array($content)) {
            $attributes = $content;
            $content    = null;
        } elseif (is_string($content)) {
            $content = htmlentities($content);
        }

        $node = $this->dom->createElement($tag, $content);

        if (count($attributes)) {
            foreach ($attributes as $key => $value) {
                $node->setAttribute($key, $value);
            }
        }

        return $node;
    }

    /**
     * Remove a node from DOM tree
     *
     * @since  1.0.7
     * @param  DOMNode $node
     * @return self
     */
    protected function removeNode(&$node)
    {
        if ($node->parentNode) {
            $node->parentNode->removeChild($node);
            $node = null;
        }

        return $this;
    }
}
