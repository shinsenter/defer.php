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

use Exception;
use shinsenter\Helpers\JsMin;
use DOMText;

trait DeferOptimizer
{
    /**
     * The main optimization function
     *
     * @since  1.0.0
     */
    protected function optimize()
    {
        // Remove all comments
        $this->removeComments();

        // Add defer.js library
        $this->initLoaderJs();
        $this->addDeferJs();

        // Page optimiztions
        $this->enablePreloading();
        $this->enableDnsPrefetch();
        $this->fixRenderBlocking();

        // Elements optimizations
        $this->optimizeDnsTags();
        $this->optimizePreloadTags();
        $this->optimizeStyleTags();
        $this->optimizeScriptTags();
        $this->optimizeImgTags();
        $this->optimizeIframeTags();
        $this->optimizeBackgroundTags();

        // Meta optimizations
        $this->addMissingMeta();
        $this->addFingerprint();

        // Minify
        $this->minifyOutputHTML();
    }

    /*
    |--------------------------------------------------------------------------
    | For defer.js
    |--------------------------------------------------------------------------
     */

    /**
     * Include the loader init script into the HTML
     *
     * @since  1.0.0
     */
    protected function initLoaderJs()
    {
        $cleanup = '//script[@id="defer-js" or @id="defer-script" or @id="polyfill-js"]|//style[@id="defer-css"]';

        foreach ($this->xpath->query($cleanup) as $node) {
            $this->removeNode($node);
        }

        $cache  = $this->cache_manager;
        $suffix = DEFER_JS_CACHE_SUFFIX;
        $time   = $this->deferjs_expiry;

        if (!$this->append_defer_js) {
            static::$deferjs_script = '';
        } elseif (empty(static::$deferjs_script)) {
            static::$deferjs_script = $cache->get('deferjs_script' . $suffix);

            if (empty(static::$deferjs_script)) {
                static::$deferjs_script = '/* ' . static::DEFERJS_URL . ' */' . @file_get_contents(static::DEFERJS_URL);
                $cache->put('deferjs_script' . $suffix, static::$deferjs_script, $time, static::DEFERJS_URL);
            }
        }

        if (empty(static::$fingerprint)) {
            static::$fingerprint = base64_decode($cache->get('fingerprint' . $suffix));

            if (empty(static::$fingerprint)) {
                static::$fingerprint = @file_get_contents(static::FINGERPRINT_URL);
                $cache->put('fingerprint' . $suffix, base64_encode(static::$fingerprint), $time);
            }
        }

        if (empty(static::$helpers)) {
            static::$helpers = $cache->get('helpers' . $suffix);

            if (empty(static::$helpers)) {
                static::$helpers = @file_get_contents(static::HELPERS_URL);
                $cache->put('helpers' . $suffix, static::$helpers, $time);
            }
        }

        // Append simple effect for deferred contents
        if (empty(static::$inline_styles)) {
            static::$inline_styles = $cache->get('inline_styles' . $suffix);

            if (empty(static::$inline_styles)) {
                static::$inline_styles = @file_get_contents(static::INLINE_CSS_URL);
                $cache->put('inline_styles' . $suffix, static::$inline_styles, $time);
            }
        }
    }

    /**
     * Include the defer.js library into the HTML
     *
     * @since  1.0.0
     */
    protected function addDeferJs()
    {
        if ($this->isAmp) {
            return;
        }

        $the_anchor = $this->head->childNodes->item(0);

        // Append defer.js library loaded script is empty
        if (!$this->append_defer_js || empty(static::$deferjs_script)) {
            $script_tag = $this->createNode(static::SCRIPT_TAG, [
                static::ATTR_SRC => static::DEFERJS_URL,
                static::ATTR_ID  => 'defer-js',
            ]);

            $this->head->insertBefore($script_tag, $the_anchor);
            $script_tag = null;
        }

        // Append helpers
        $extra_scripts   = (array) $this->loader_scripts;
        $extra_scripts[] = '"IntersectionObserver"in window||deferscript("' . static::POLYFILL_URL . '","polyfill-js",1)';
        $extra_scripts[] = static::$helpers;

        $script = static::$deferjs_script . implode(';', array_filter($extra_scripts));

        if (!empty($script)) {
            $script_tag = $this->createNode(static::SCRIPT_TAG, trim($script), [static::ATTR_ID => 'defer-script']);

            $this->head->insertBefore($script_tag, $the_anchor);
            $script_tag = null;
        }

        // Append CSS block
        if ($this->use_css_fadein_effects) {
            $style_tag = $this->createNode(static::STYLE_TAG, static::$inline_styles, [static::ATTR_ID => 'defer-css']);

            $this->head->insertBefore($style_tag, $the_anchor);
            $style_tag = null;
        }

        // Free memory
        $the_anchor = null;
    }

    /**
     * Add library's fingerprint
     *
     * @since  1.0.0
     */
    protected function addFingerprint()
    {
        if (empty(static::$fingerprint)) {
            return;
        }

        $fingerprint = $this->dom->createComment(static::$fingerprint);
        $this->body->parentNode->appendChild($fingerprint);
        $fingerprint = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Meta optimizations
    |--------------------------------------------------------------------------
     */

    /**
     * Optimize parsed <comment> tags
     *
     * @since  1.0.0
     */
    protected function removeComments()
    {
        foreach ($this->comment_cache as $node) {
            $this->removeNode($node);
        }
    }

    /**
     * Optimize parsed <dns> tags
     *
     * @since  1.0.0
     */
    protected function optimizeDnsTags()
    {
    }

    /**
     * Optimize parsed <preload> tags
     *
     * @since  1.0.0
     */
    protected function optimizePreloadTags()
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Page optimizations
    |--------------------------------------------------------------------------
     */

    /**
     * Enable DNS prefetch
     *
     * @since  1.0.0
     */
    protected function enableDnsPrefetch()
    {
        if (!$this->enable_dns_prefetch) {
            return;
        }

        foreach ($this->dns_cache as $node) {
            $this->removeNode($node);
        }

        $new_cache  = [];
        $the_anchor = $this->head->childNodes->item(0);

        foreach ($this->dns_map as $domain => $node) {
            if (is_a($node, \DOMElement::class) && $node->nodeName == static::LINK_TAG) {
                $link_tag = $node;
            } else {
                $link_tag = $this->createNode(static::LINK_TAG, [
                    static::ATTR_REL  => static::REL_DNSPREFETCH,
                    static::ATTR_HREF => $domain,
                ]);
            }

            $this->head->insertBefore($link_tag, $the_anchor);
            $new_cache[] = $link_tag;
        }

        foreach ($this->preconnect_map as $domain => $node) {
            if (is_a($node, \DOMElement::class) && $node->nodeName == static::LINK_TAG) {
                $link_tag = $node;
            } else {
                $link_tag = $this->createNode(static::LINK_TAG, [
                    static::ATTR_REL         => static::REL_PRECONNECT,
                    static::ATTR_HREF        => $domain,
                    static::ATTR_CROSSORIGIN => 'anonymous',
                ]);
            }

            $this->head->insertBefore($link_tag, $the_anchor);
            $new_cache[] = $link_tag;
        }

        $the_anchor = null;

        $this->dns_cache = $new_cache;
    }

    /**
     * Enable preloading contents
     * Generate <link rel="preload"> for contents in the HTML
     *
     * @since  1.0.0
     */
    protected function enablePreloading()
    {
        if (!$this->enable_preloading) {
            return;
        }

        foreach ($this->preload_cache as $node) {
            $this->removeNode($node);
        }

        if (!empty($this->preload_map)) {
            $the_anchor = $this->head->childNodes->item(0);

            foreach ($this->preload_map as $url => $node) {
                if (empty($url)) {
                    continue;
                }

                $as = $this->getPreloadType($node);

                if (empty($as)) {
                    continue;
                }

                $link_tag = $this->createNode(static::LINK_TAG, [
                    static::ATTR_REL  => static::REL_PRELOAD,
                    static::ATTR_AS   => $as,
                    static::ATTR_HREF => $url,
                ]);

                if (is_a($node, \DOMElement::class) && $node->hasAttribute(static::ATTR_CHARSET)) {
                    $link_tag->setAttribute(static::ATTR_CHARSET, $node->getAttribute(static::ATTR_CHARSET));
                }

                $this->head->insertBefore($link_tag, $the_anchor);
                $link_tag = null;
            }

            $the_anchor = null;
        }
    }

    /**
     * Fix render blocking objects
     *
     * @since  1.0.0
     */
    protected function fixRenderBlocking()
    {
        if (!$this->fix_render_blocking) {
            return;
        }

        foreach ($this->style_cache as $node) {
            if ($node->parentNode && $node->parentNode->nodeName !== static::HEAD_TAG) {
                $this->head->appendChild($node);
            }
        }

        foreach ($this->script_cache as $node) {
            if ($node->parentNode) {
                $this->body->appendChild($node);
            }
        }
    }

    /**
     * Add missing must-have meta tags
     *
     * @since  1.0.4
     */
    protected function addMissingMeta()
    {
        if (!$this->add_missing_meta_tags) {
            return;
        }

        $the_anchor = $this->head->childNodes->item(0);

        // Check if the meta viewport tag does not exist
        $attempt = $this->xpath->query('//meta[@charset or contains(@http-equiv,"Content-Type")]');

        if (!$attempt || $attempt->length < 1) {
            $this->head->insertBefore($this->createNode(static::META_TAG, [
                static::ATTR_CHARSET => $this->charset,
            ]), $the_anchor);
        } else {
            $this->head->insertBefore($attempt->item(0), $the_anchor);
        }

        // Check if the meta viewport tag does not exist
        $attempt = $this->xpath->query('//meta[@name="viewport" and contains(@content,"initial-scale")]');

        if (!$attempt || $attempt->length < 1) {
            $this->head->insertBefore($this->createNode(static::META_TAG, [
                static::ATTR_NAME    => 'viewport',
                static::ATTR_CONTENT => 'width=device-width,initial-scale=1',
            ]), $the_anchor);
        } else {
            $this->head->insertBefore($attempt->item(0), $the_anchor);
        }

        $the_anchor = null;
    }

    /**
     * Minify output HTML
     *
     * @since  1.0.0
     */
    protected function minifyOutputHTML()
    {
        if (!$this->minify_output_html) {
            return;
        }

        $this->dom->normalizeDocument();
        $nodes = $this->xpath->query(static::NORMALIZE_XPATH);

        foreach ($nodes as $node) {
            $trimmed = trim(preg_replace('/\s+/', ' ', $node->nodeValue));

            if (empty($trimmed)) {
                if ($node->previousSibling && $node->nextSibling) {
                    $trimmed = ' ';
                }
            } else {
                if ($node->previousSibling) {
                    $trimmed = ' ' . $trimmed;
                } elseif ($node->nextSibling) {
                    $trimmed = $trimmed . ' ';
                }
            }

            if ($trimmed != $node->nodeValue) {
                $node->nodeValue = $trimmed;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Elements optimizations
    |--------------------------------------------------------------------------
     */

    /**
     * Optimize parsed <style> tags
     *
     * @since  1.0.0
     */
    protected function optimizeStyleTags()
    {
        if (!$this->enable_defer_css) {
            return;
        }

        foreach ($this->style_cache as $node) {
            if ($this->isBlacklistedNode($node, static::ATTR_HREF)) {
                continue;
            }

            if ($node->nodeName == static::LINK_TAG) {
                $this->deferWebFont($node);

                continue;
            }

            $code = trim($node->nodeValue);

            if (empty($code) && $node->parentNode) {
                $node->parentNode->removeChild($node);

                continue;
            }

            // Minify the css code
            // See: https://gist.github.com/clipperhouse/1201239/cad48570925a4f5ff0579b654e865db97d73bcc4
            $code = preg_replace('/\s*([,>+;:!}{]{1})\s*/', '$1', $code);
            $code = str_replace([';}', "\r", "\n"], ['}', '', ''], $code);

            // Strip comments
            // See: https://gist.github.com/orangexception/1292778
            $code = preg_replace('/\/\*(?:(?!\*\/).)*\*\//', '', $code);

            // Update the node content
            if ($node->nodeValue != $code) {
                $node->nodeValue = htmlspecialchars($code);
            }

            if(preg_match('/url\s*\(/', $code)) {
                // Make a noscript fallback
                $this->makeNoScript($node);

                // The switch to the right media type when it is loaded
                $node->setAttribute(static::ATTR_ONLOAD, sprintf(
                    'var self=this;defer(function(){self.media="%s";self.removeAttribute("onload")},1)',
                    addslashes($node->getAttribute(static::ATTR_MEDIA) ?: 'all')
                ));

                // Make a fake media type, force browser to load this as the lowest priority
                $node->setAttribute(static::ATTR_MEDIA, 'screen and (max-width:1px)');
            }
        }
    }

    /**
     * Optimize parsed <script> tags
     *
     * @since  1.0.0
     */
    protected function optimizeScriptTags()
    {
        foreach ($this->script_cache as $node) {
            if ($this->isBlacklistedNode($node, static::ATTR_SRC)) {
                continue;
            }

            if ($this->enable_defer_scripts) {
                $node->setAttribute(static::ATTR_TYPE, 'deferjs');
                $node->removeAttribute(static::ATTR_DEFER);
            }

            $code = trim($node->nodeValue);

            if (!empty($code)) {
                if (strstr($code, '<!--') !== false) {
                    $code = preg_replace('/(^\s*<!--\s*|\s*\/\/\s*-->\s*$)/', '', $code);
                }

                $minify = null;

                try {
                    $minify = JsMin::minify($code);
                } catch (Exception $error) {
                    $minify = null;
                }

                if ($minify) {
                    $code = trim($minify);
                }

                if ($node->nodeValue != $code) {
                    $node->nodeValue = htmlspecialchars($code);
                }
            }
        }
    }

    /**
     * Optimize parsed <img> tags
     *
     * @since  1.0.0
     */
    protected function optimizeImgTags()
    {
        if (!$this->enable_defer_images) {
            return;
        }

        foreach ($this->img_cache as $node) {
            if ($this->isBlacklistedNode($node, static::ATTR_SRC)) {
                continue;
            }

            $replaced = $this->makeLazySrcset($node);
            $replaced = $this->makeLazySrc($node) || $replaced;

            if ($replaced && !$node->hasAttribute(static::ATTR_SRC)) {
                if ($this->empty_gif) {
                    $node->setAttribute(static::ATTR_SRC, $this->empty_gif);
                } else {
                    $this->setPlaceholderSrc($node);
                }

                $this->addBackgroundColor($node);
            }
        }
    }

    /**
     * Optimize parsed <iframe> tags
     *
     * @since  1.0.0
     */
    protected function optimizeIframeTags()
    {
        if (!$this->enable_defer_iframes) {
            return;
        }

        foreach ($this->iframe_cache as $node) {
            if ($this->isBlacklistedNode($node, static::ATTR_SRC)) {
                continue;
            }

            $replaced = $this->makeLazySrc($node);

            if ($replaced && !$node->hasAttribute(static::ATTR_SRC)) {
                if ($this->empty_src) {
                    $node->setAttribute(static::ATTR_SRC, $this->empty_src);
                }

                $this->addBackgroundColor($node);
            }
        }
    }

    /**
     * Optimize all tags contain background image
     *
     * @since  1.0.4
     */
    protected function optimizeBackgroundTags()
    {
        if (!$this->enable_defer_background) {
            return;
        }

        foreach ($this->bg_cache as $node) {
            $styles     = $node->getAttribute(static::ATTR_STYLE);
            $props      = array_filter(explode(';', $styles));
            $safe_props = [];

            foreach ($props as $prop) {
                if (!preg_match('/url\s*\([^\)]+\)/i', $prop)) {
                    $safe_props[] = trim($prop);
                }
            }

            $node->setAttribute(static::ATTR_DATA_STYLE, implode(';', $props));

            if (!empty($safe_props)) {
                $node->setAttribute(static::ATTR_STYLE, implode(';', $safe_props));
            } else {
                $node->removeAttribute(static::ATTR_STYLE);
            }

            $styles = $props = $safe_props = $prop = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Other helper functions
    |--------------------------------------------------------------------------
     */

    /**
     * Cleanup library cache directory
     *
     * @since  1.0.3
     */
    protected function cleanupLibraryCache()
    {
        @mkdir($dir = dirname(static::DEFERJS_CACHE), 0755, true);

        $files = glob($dir . '/*.php', GLOB_MARK);

        if (!empty($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Get default defer timeout from options
     *
     * @since  1.0.0
     * @param  int $add
     * @return int
     */
    protected function getDeferTime($add = 0)
    {
        return (int) ($this->default_defer_time + $add);
    }

    /**
     * Check if a node or a src is blacklisted
     *
     * @since  1.0.0
     * @param  DOMNode $node
     * @param  string  $src_attr
     * @return bool
     */
    protected function isBlacklistedNode($node, $src_attr = 'src')
    {
        $src       = $node->getAttribute($src_attr);
        $blacklist = $this->do_not_optimize;

        if (is_array($blacklist)) {
            foreach ($blacklist as $pattern) {
                $regex = '#' . str_replace('#', '\#', $pattern) . '#';

                try {
                    if (preg_match($regex, $src . $node->nodeValue)) {
                        return true;
                    }
                } catch (Exception $error) {
                    $error = null;
                }
            }
        }

        return false;
    }

    /**
     * Check if a URL is a webfont css
     *
     * @since  1.0.0
     * @param  string $src
     * @return bool
     */
    protected function isWebfontUrl($src)
    {
        $list = $this->web_fonts_patterns;

        if (!empty($src) && is_array($list)) {
            foreach ($list as $pattern) {
                $regex = '#' . str_replace('#', '\#', $pattern) . '#';

                try {
                    if (preg_match($regex, $src)) {
                        return true;
                    }
                } catch (Exception $error) {
                    $error = null;
                }
            }
        }

        return false;
    }

    /**
     * Defer a webfont tag
     *
     * @since  1.0.4
     * @param DOMNode $node
     */
    protected function deferWebFont($node)
    {
        if (!$this->defer_web_fonts || $node->hasAttribute(static::ATTR_ONLOAD)) {
            return;
        }

        $src = $node->getAttribute(static::ATTR_HREF);

        if ($this->isWebfontUrl($src)) {
            // Make a noscript fallback
            $this->makeNoScript($node);

            // The switch to the right media type when it is loaded
            $node->setAttribute(static::ATTR_ONLOAD, sprintf(
                'var self=this;defer(function(){self.media="%s";self.removeAttribute("onload")},2)',
                addslashes($node->getAttribute(static::ATTR_MEDIA) ?: 'all')
            ));

            // Make a fake media type, force browser to load this as the lowest priority
            $node->setAttribute(static::ATTR_MEDIA, 'screen and (max-width:1px)');
        }
    }

    /**
     * Get right content type for <link rel="preload">
     *
     * @since  1.0.0
     * @param  string/DOMNode $node
     * @return string
     */
    protected function getPreloadType($node)
    {
        $as = null;

        if (is_a($node, \DOMElement::class)) {
            switch ($node->nodeName) {
                case static::LINK_TAG:
                    if (in_array($node->getAttribute(static::ATTR_REL), [static::REL_DNSPREFETCH, static::REL_PRECONNECT])) {
                        break;
                    }
                    $as = static::PRELOAD_STYLE;
                    break;
                case static::STYLE_TAG:
                    $as = static::PRELOAD_STYLE;
                    break;
                case static::SCRIPT_TAG:
                    $as = static::PRELOAD_SCRIPT;
                    break;
                case static::IMG_TAG:
                    // $as = static::PRELOAD_IMAGE;
                    break;
                case static::AUDIO_TAG:
                    // $as = static::PRELOAD_AUDIO;
                    break;
                case static::VIDEO_TAG:
                    // $as = static::PRELOAD_VIDEO;
                    break;
                case static::EMBED_TAG:
                    // $as = static::PRELOAD_EMBED;
                    break;
                case static::FRAME_TAG:
                case static::IFRAME_TAG:
                    // $as = static::PRELOAD_DOCUMENT;
                    break;
                default:
                    // $as = static::PRELOAD_FETCH;
                    break;
            }
        } elseif (is_string($node)) {
            $as = $node;
        }

        return $as;
    }

    /**
     * Add random background color for a node
     *
     * @since  1.0.3
     * @param DOMNode $node
     * @see    https://github.com/axe312ger/sqip
     */
    protected function addBackgroundColor($node)
    {
        if ($this->use_color_placeholder) {
            $placeholder = 'background-color:hsl(' . rand(1, 360) . ',100%,96%);';
            $style       = (string) $node->getAttribute(static::ATTR_STYLE);
            $node->setAttribute(static::ATTR_STYLE, $placeholder . $style);
        }
    }

    /**
     * Set placeholder src for the media
     *
     * @since  1.0.3
     * @param DOMNode $node
     */
    protected function setPlaceholderSrc($node)
    {
        $w = (int) $node->getAttribute(static::ATTR_WIDTH);
        $h = (int) $node->getAttribute(static::ATTR_HEIGHT);

        if ($w < 1 || $h < 1) {
            $w = $h = 1;
        }

        $placeholder = str_replace(['<', '>'], ['%3C', '%3E'], sprintf(static::SVG_PLACEHOLDER, $w, $h));
        $node->setAttribute(static::ATTR_SRC, 'data:image/svg+xml,' . $placeholder);
    }

    /**
     * Append a <noscript> tag for content fallback
     *
     * @since  1.0.3
     * @param DOMNode $node
     */
    protected function makeNoScript($node)
    {
        // Create noscript tag for normal image fallback
        if (!$this->debug_mode && $node->parentNode) {
            if ($node->nodeName !== static::LINK_TAG
                && $node->nodeName !== static::STYLE_TAG
                && $node->parentNode->nodeName === static::HEAD_TAG) {
                $this->body->appendChild($node);
            }

            // If there is an existing noscript, then do nothing
            if ($node->nextSibling && $node->nextSibling->nodeName == static::NOSCRIPT_TAG) {
                return;
            }

            // Append normal image into the <noscript> tag
            $clone    = $node->cloneNode(true);
            $noscript = $this->createNode(static::NOSCRIPT_TAG);
            $noscript->appendChild($clone);

            $node->parentNode->insertBefore($noscript, $node->nextSibling);

            // Cleanup
            $noscript = $clone = null;
        }
    }

    /**
     * Normalize an attribute by given attribute list
     *
     * @since  1.0.6
     * @param DOMNode $node
     * @param string  $attribute
     * @param array   $try_attributes
     * @param bool    $fix_url
     */
    protected function normalizeAttribute($node, $attribute, $try_attributes = [], $fix_url = true)
    {
        $value = null;

        foreach ($try_attributes as $attr) {
            if ($node->hasAttribute($attr)) {
                $value = $node->getAttribute($attr);
                $node->removeAttribute($attr);
            }
        }

        if (!is_null($value)) {
            $node->setAttribute($attribute, $value);
        }

        if ($fix_url) {
            $this->normalizeUrl($node, $attribute, false);
        }

        return $node->getAttribute($attribute);
    }

    /**
     * Add data-src attribute to the node
     *
     * @since  1.0.6
     * @param  DOMNode $node
     * @return bool
     */
    protected function makeLazySrc($node)
    {
        // Backup old placeholder image
        $org = $this->normalizeAttribute($node, static::ATTR_SRC);

        // Normalize the src attribute
        $src = $this->normalizeAttribute($node, static::ATTR_SRC, static::UNIFY_OTHER_LAZY_SRC);

        if (!empty($src)) {
            // Make a noscript fallback
            $this->makeNoScript($node);

            // Assign new attribute
            $node->setAttribute(static::ATTR_DATA_SRC, $src);

            // Remove src attribute if it is not placeholder
            if ($org != $src) {
                $node->setAttribute(static::ATTR_SRC, $org);
            } else {
                $node->removeAttribute(static::ATTR_SRC);
            }

            return true;
        }

        return false;
    }

    /**
     * Add data-srcset attribute to the node
     *
     * @since  1.0.6
     * @param  DOMNode $node
     * @return bool
     */
    protected function makeLazySrcset($node)
    {
        // Normalize the sizes attribute
        $this->normalizeAttribute($node, static::ATTR_SIZES, static::UNIFY_OTHER_LAZY_SIZES, false);

        // Normalize the srcset attribute
        $src = $this->normalizeAttribute($node, static::ATTR_SRCSET, static::UNIFY_OTHER_LAZY_SRCSET);

        if (!empty($src)) {
            // Assign new attribute
            $node->removeAttribute(static::ATTR_SRCSET);
            $node->setAttribute(static::ATTR_DATA_SRCSET, $src);

            return true;
        }

        return false;
    }
}
