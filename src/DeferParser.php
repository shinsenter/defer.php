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

namespace AppSeeds;

trait DeferParser
{
    // To fix html entities decode
    public static $__html_mapping = null;

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

    // Bugs with script tags which contains HTML
    protected $bug_script_templates;

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

        // Preload defer.js
        if (!$this->append_defer_js) {
            $this->preload_map[static::DEFERJS_URL] = static::PRELOAD_SCRIPT;
        }

        // Bug fixes
        $this->bug72288_body        = null;
        $this->bug_script_templates = [];

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
            $encoding = mb_detect_encoding($html, 'auto');

            if ($encoding === false || $encoding === 'ASCII') {
                $encoding = 'UTF-8';
            }

            $this->charset = $encoding;
        }

        // Convert HTML into HTML entities
        $html = $this->charset2entity($html, $this->charset);

        // Force HTML5 doctype
        $html = preg_replace('/<!DOCTYPE html[^>]*>/i', '<!DOCTYPE html>', $html, 1);
        $html = preg_replace('/<\?xml[^>]*>/i', '', $html, 1);
        $html = $this->script_encode($html);

        // Create DOM document
        $this->dom->preserveWhiteSpace = true;
        $this->dom->loadHTML($html);
        $this->dom->formatOutput = false;

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
            $document = $this->xpath->query('/html')->item(0);
            $this->addClass($document, ['no-deferjs']);
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

            if (
                $node->hasAttribute(static::ATTR_TYPE) &&
                stripos($node->getAttribute(static::ATTR_TYPE), 'css') !== false
            ) {
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

            if (
                $node->hasAttribute(static::ATTR_TYPE) &&
                stripos($node->getAttribute(static::ATTR_TYPE), 'javascript') !== false
            ) {
                $node->removeAttribute(static::ATTR_TYPE);
            }

            if (
                $node->hasAttribute(static::ATTR_LANGUAGE) &&
                stripos($node->getAttribute(static::ATTR_LANGUAGE), 'javascript') !== false
            ) {
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
                if (
                    $node->nodeName == static::IMG_TAG &&
                    !$node->hasAttribute(static::ATTR_ALT)
                ) {
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
                if (
                    $node->nodeName == static::IFRAME_TAG &&
                    !$node->hasAttribute(static::ATTR_TITLE)
                ) {
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

            // Remove ads
            $ads_excludes = implode('|', [
                'ads',
                'click',
                'googletags',
                'publisher',
                'gpt\.js',
                'adservice\.google',
                'ampproject\.org',
                'doubleclick\.net',
                'facebook\.com',
                'facebook\.net',
                'googlesyndication\.com',
                'twimg\.com',
                'twitter\.com',
            ]);

            if (stripos($src, 'http') !== 0 || preg_match('#' . $ads_excludes . '#', $src)) {
                $preload_flag = false;
            }

            if ($preload_flag) {
                $domain = preg_replace('#^(https?://[^/\?]+)([/\?]?.*)?$#', '$1', $src);
                $this->addPreloadMap($src, $node)->addDnsMap($domain, $node);
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
        return $this->xpath->query('//html[@amp]')->length > 0 ||
            strpos($html, '&#x26A1;') !== false ||
            strpos($html, '⚡') !== false;
    }

    /**
     * Convert input string into html entities
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
     * Detect and convert html entities into normal text
     *
     * @since  1.0.7
     * @param  string $html
     * @param  string $charset
     * @return string
     */
    protected function entity2charset($html, $charset)
    {
        $encoding = mb_detect_encoding($html, 'auto');

        if ($encoding === false || $encoding === 'ASCII') {
            $encoding = 'HTML-ENTITIES';
        }

        if ($encoding == 'HTML-ENTITIES') {
            $html = $this->escapeHtmlEntity($html, false);
        }

        if ($this->charset !== $encoding) {
            $html = mb_convert_encoding($html, $charset, $encoding);
        }

        if ($encoding == 'HTML-ENTITIES') {
            $html = $this->escapeHtmlEntity($html, true);
        }

        // Convert &#[0-9]+; entities to UTF-8
        if ($this->charset == 'UTF-8') {
            $html = preg_replace_callback('/(&#[0-9]+;)/', function ($m) {
                return mb_convert_encoding($m[1], 'UTF-8', 'HTML-ENTITIES');
            }, $html);
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
        } elseif (in_array($tag, [static::SCRIPT_TAG]) && is_string($content)) {
            $content = htmlspecialchars($content);
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

    /**
     * Prepend HTML
     *
     * @since  1.0.15
     * @param  DOMNode $node
     * @param  string  $html
     * @return self
     */
    protected function prependHtml(&$node, $html)
    {
        $tpl = $this->dom->createDocumentFragment();
        $tpl->appendXML($html);

        $the_anchor = $node->childNodes->item(0);
        $node->insertBefore($tpl, $the_anchor);

        return $node;
    }

    /**
     * Append HTML
     *
     * @since  1.0.15
     * @param  DOMNode $node
     * @param  string  $html
     * @return self
     */
    protected function appendHtml(&$node, $html)
    {
        $tpl = $this->dom->createDocumentFragment();
        $tpl->appendXML($html);

        $node->appendChild($tpl);

        return $node;
    }

    /**
     * Add the resouce URL to the preload list
     *
     * @since  1.0.9
     * @param  string  $url
     * @param  DOMNode $node
     * @return self
     */
    protected function addPreloadMap($url, $node)
    {
        $rel = $node->getAttribute(static::ATTR_REL);

        // Add the resouce URL to the preload list
        if (!in_array($rel, [static::REL_DNSPREFETCH, static::REL_PRECONNECT])) {
            $this->preload_map[$url] = $node;
        }

        return $this;
    }

    /**
     * Add the domain to the dns list
     *
     * @since  1.0.9
     * @param  string  $domain
     * @param  DOMNode $node
     * @return self
     */
    protected function addDnsMap($domain, $node)
    {
        $rel = $node->getAttribute(static::ATTR_REL);

        // Add the domain to the dns list
        if (!empty($domain)) {
            $this->dns_map[$domain]        = $rel == static::REL_DNSPREFETCH ? $node : $rel;
            $this->preconnect_map[$domain] = $rel == static::REL_PRECONNECT ? $node : $rel;
        }

        return $this;
    }

    /**
     * Remove class names from a node
     *
     * @since  1.0.9
     * @param  DOMNode $node
     * @param  array   $class_list
     * @return self
     */
    protected function removeClass($node, $class_list)
    {
        $original_class = (string) $node->getAttribute(static::ATTR_CLASS);
        $current_class  = explode(' ', $original_class);
        $current_class  = array_filter($current_class, function ($item) use ($class_list) {
            return !in_array($item, (array) $class_list);
        });
        $current_class = implode(' ', array_filter(array_unique($current_class)));

        if ($current_class !== $original_class) {
            $this->setOrRemoveAttribute($node, static::ATTR_CLASS, $current_class);
        }

        return $this;
    }

    /**
     * Add class names to a node
     *
     * @since  1.0.9
     * @param  DOMNode $node
     * @param  array   $class_list
     * @return self
     */
    protected function addClass($node, $class_list)
    {
        $original_class = (string) $node->getAttribute(static::ATTR_CLASS);
        $current_class  = explode(' ', $original_class);
        $current_class  = array_merge($current_class, (array) $class_list);
        $current_class  = implode(' ', array_filter(array_unique($current_class)));

        if ($current_class !== $original_class) {
            $this->setOrRemoveAttribute($node, static::ATTR_CLASS, $current_class);
        }

        return $this;
    }

    /**
     * Set or remove a node attribute
     *
     * @since  1.0.9
     * @param  DOMNode $node
     * @param  string  $attr
     * @param  string  $value
     * @return self
     */
    protected function setOrRemoveAttribute($node, $attr, $value)
    {
        if (empty($value)) {
            $node->removeAttribute($attr);
        } else {
            $node->setAttribute($attr, $value);
        }

        return $this;
    }

    /**
     * This is used to fix script tags which contain html templates
     *
     * @since  1.0.10
     * @param  $html
     * @return string
     */
    protected function script_encode($html)
    {
        return preg_replace_callback('/(<script[^>]*>)(.*?)(<\/script>)/si', function ($matches) {
            if (!preg_match('/type=/i', $matches[1]) || strpos($matches[1], 'text/javascript') !== false) {
                $output = $matches[0];

                if (preg_match('/<\/[^>]*>/', $matches[2])) {
                    $next = preg_replace('/<\/([^>]*)>/', '<\\/$1>', $matches[2]);
                    $output = "{$matches[1]}{$next}{$matches[3]}";
                }

                return $output;
            }

            $next = '@@@SCRIPT@@@' . count($this->bug_script_templates) . '@@@SCRIPT@@@';

            $this->bug_script_templates[$next] = trim($matches[2]);

            return "{$matches[1]}{$next}{$matches[3]}";
        }, $html);
    }

    /**
     * This is used revert script tags to its original content
     *
     * @since  1.0.10
     * @param  array  $matches
     * @param  mixed  $html
     * @return string
     */
    protected function script_decode($html)
    {
        $result = str_replace(
            array_keys($this->bug_script_templates),
            array_values($this->bug_script_templates),
            $html
        );

        $this->bug_script_templates = [];

        return $result;
    }

    /**
     * Escape / unescape regular HTML entities
     *
     * @since  1.0.17
     * @param  string $html
     * @param  bool   $revert = false
     * @return string
     */
    protected function escapeHtmlEntity($html, $revert = false)
    {
        // Initial HTML entity optimizer
        if (is_null(static::$__html_mapping)) {
            $mapping = array_values(get_html_translation_table(HTML_SPECIALCHARS));

            static::$__html_mapping = [
                'from' => $mapping,
                'to'   => array_map(function ($v) {
                    return str_replace(['&', ';'], ['@&@', '@;@'], $v);
                }, $mapping),
            ];

            unset($mapping);
        }

        // Process the HTML
        if ($revert) {
            $html = str_replace(
                static::$__html_mapping['to'],
                static::$__html_mapping['from'],
                $html
            );
        } else {
            $html = str_replace(
                static::$__html_mapping['from'],
                static::$__html_mapping['to'],
                $html
            );
        }

        return $html;
    }
}
