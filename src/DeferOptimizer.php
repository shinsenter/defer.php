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

trait DeferOptimizer
{
    /**
     * The main optimization function
     *
     * @since  1.0.0
     */
    protected function optimize()
    {
        // Add defer.js library
        $this->initLoaderJs();
        $this->addDeferJs();

        // Meta optimizations
        $this->optimizeCommentTags();
        $this->optimizeDnsTags();
        $this->optimizePreloadTags();

        // Page optimiztions
        $this->enablePreloading();
        $this->enableDnsPrefetch();
        $this->fixRenderBlocking();

        // Elements optimizations
        $this->optimizeStyleTags();
        $this->optimizeScriptTags();
        $this->optimizeImgTags();
        $this->optimizeIframeTags();
        $this->optimizeBackgroundTags();

        // Minify
        $this->minifyOutputHTML();
        $this->addFingerprint();
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
        // Load and cache the defer.js library
        if (file_exists(static::DEFERJS_CACHE) && time() - filemtime(static::DEFERJS_CACHE) < static::DEFERJS_EXPIRY) {
            require_once static::DEFERJS_CACHE;
        } else {
            $cache_template = "<?php\n" .
                "/* https://github.com/shinsenter/defer.js cached on %s */\n" .
                'use \shinsenter\Defer as DeferJs;' .
                'DeferJs::$deferjs_script="%s";' .
                'DeferJs::$helpers="%s";' .
                'DeferJs::$fingerprint=base64_decode("%s");';

            $comment = '/* ' . static::DEFERJS_URL . ' */';
            $source  = @file_get_contents(static::DEFERJS_URL);

            static::$deferjs_script = $comment . $source;
            static::$fingerprint    = @file_get_contents(static::FINGERPRINT_URL);
            static::$helpers        = @file_get_contents(static::HELPERS_URL);

            $this->cleanupLibraryCache();
            @file_put_contents(
                static::DEFERJS_CACHE,
                sprintf(
                    $cache_template,
                    date('Y-m-d H:i:s'),
                    str_replace(['\\', '"'], ['\\\\', '\"'], static::$deferjs_script),
                    str_replace(['\\', '"'], ['\\\\', '\"'], static::$helpers),
                    base64_encode(static::$fingerprint)
                )
            );
        }

        if (!$this->append_defer_js) {
            static::$deferjs_script = '';
        }

        // Append simple effect for deferred contents
        $style_tag = $this->dom->createElement(static::STYLE_TAG, static::FADEIN_EFFECT);
        $this->head->appendChild($style_tag);
        $style_tag = null;
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

        $cleanup = '//script[@id="defer-js" or @id="defer-helpers" or @id="defer-script"]|//link[@id="polyfill-js"]';

        foreach ($this->xpath->query($cleanup) as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
                $node = null;
            }
        }

        $the_anchor = $this->head->childNodes->item(0);

        // Append polyfill
        $script_tag = $this->dom->createElement(static::SCRIPT_TAG);
        $script_tag->setAttribute(static::ATTR_SRC, static::POLYFILL_URL);
        $script_tag->setAttribute(static::ATTR_ID, 'polyfill-js');
        $this->head->insertBefore($script_tag, $the_anchor);
        $script_tag = null;

        // Append defer.js library loaded script is empty
        if (empty(static::$deferjs_script)) {
            $script_tag = $this->dom->createElement(static::SCRIPT_TAG);
            $script_tag->setAttribute(static::ATTR_SRC, static::DEFERJS_URL);
            $script_tag->setAttribute(static::ATTR_ID, 'defer-js');
            $this->head->insertBefore($script_tag, $the_anchor);
            $script_tag = null;
        }

        // Append helpers
        $extra_scripts   = (array) $this->loader_scripts;
        $extra_scripts[] = static::$helpers;

        if (!empty($script = static::$deferjs_script . implode(';', array_filter($extra_scripts)))) {
            $script_tag = $this->dom->createElement(static::SCRIPT_TAG, trim($script));
            $script_tag->setAttribute(static::ATTR_ID, 'defer-script');
            $this->head->insertBefore($script_tag, $the_anchor);
            $script_tag = null;
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
        if ($this->enable_defer_scripts) {
            $script_tag = $this->dom->createElement(static::SCRIPT_TAG, trim(static::DEFER_INLINE));
            $this->head->appendChild($script_tag);
            $script_tag = null;
        }

        if (empty(static::$fingerprint)) {
            return;
        }

        $fingerprint = $this->dom->createComment(static::$fingerprint);
        $this->body->appendChild($fingerprint);
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
    protected function optimizeCommentTags()
    {
        foreach ($this->comment_cache as $node) {
            $node->parentNode->removeChild($node);
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
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
                $node = null;
            }
        }

        $new_cache  = [];
        $the_anchor = $this->head->childNodes->item(0);

        foreach ($this->dns_map as $domain => $node) {
            if (is_a($node, \DOMElement::class) && $node->nodeName == static::LINK_TAG) {
                $link_tag = $node;
            } else {
                $link_tag = $this->dom->createElement(static::LINK_TAG);
                $link_tag->setAttribute(static::ATTR_REL, static::REL_DNSPREFETCH);
                $link_tag->setAttribute(static::ATTR_HREF, $domain);
            }

            $this->head->insertBefore($link_tag, $the_anchor);
            $new_cache[] = $link_tag;
        }

        foreach ($this->preconnect_map as $domain => $node) {
            if (is_a($node, \DOMElement::class) && $node->nodeName == static::LINK_TAG) {
                $link_tag = $node;
            } else {
                $link_tag = $this->dom->createElement(static::LINK_TAG);
                $link_tag->setAttribute(static::ATTR_REL, static::REL_PRECONNECT);
                $link_tag->setAttribute(static::ATTR_HREF, $domain);
                $link_tag->setAttribute(static::ATTR_CROSSORIGIN, static::ATTR_CROSSORIGIN);
            }

            $this->head->insertBefore($link_tag, $the_anchor);
            $new_cache[] = $link_tag;
        }

        $this->dns_cache = $new_cache;
        $the_anchor      = null;
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
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
                $node = null;
            }
        }

        if (!empty($this->preload_map)) {
            $the_anchor = $this->head->childNodes->item(0);

            foreach ($this->preload_map as $url => $node) {
                if (empty($url)
                    || $this->isWebfontUrl($url)
                    || empty($as = $this->getPreloadType($node))) {
                    continue;
                }

                $link_tag = $this->dom->createElement(static::LINK_TAG);
                $link_tag->setAttribute(static::ATTR_REL, static::REL_PRELOAD);
                $link_tag->setAttribute(static::ATTR_AS, $as);
                $link_tag->setAttribute(static::ATTR_HREF, $url);

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

        $the_anchor = $this->body->childNodes->item(0);

        foreach ($this->style_cache as $node) {
            $this->body->insertBefore($node, $the_anchor);
        }

        foreach ($this->script_cache as $node) {
            $this->body->appendChild($node);
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

        foreach ($this->xpath->query('//text()[not(normalize-space())]') as $node) {
            $node->nodeValue = ' ';
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
            $src = $node->getAttribute(static::ATTR_HREF);

            if ($this->isBlacklistedNode($node, $src)) {
                continue;
            }

            if ($node->nodeName == static::LINK_TAG) {
                $this->deferWebFont($node);

                continue;
            }

            $code = $node->textContent;

            // Strip comments
            // See: https://gist.github.com/orangexception/1292778
            $code = preg_replace('/\/\*(?:(?!\*\/).)*+\*\//', '', $code);

            // Minify the css code
            // See: https://gist.github.com/clipperhouse/1201239/cad48570925a4f5ff0579b654e865db97d73bcc4
            $code = preg_replace('/\s*([,>+;:!}{]{1})\s*/', '$1', $code);
            $code = trim(str_replace(';}', '}', $code));

            if (!empty($code)) {
                $node->textContent = $code;
            } elseif ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }

            $code = null;
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
            $src = $node->getAttribute(static::ATTR_SRC);

            if ($this->isBlacklistedNode($node, $src)) {
                continue;
            }

            if ($this->enable_defer_scripts) {
                $node->setAttribute(static::ATTR_TYPE, 'deferscript');
            }

            $rewrite = false;
            $code    = $node->textContent;

            if (!empty($code)) {
                try {
                    $code    = JsMin::minify($code);
                    $rewrite = true;
                } catch (Exception $e) {
                    unset($e);
                }
            }

            if ($rewrite) {
                $node->textContent = $code;
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
            $src = $node->getAttribute(static::ATTR_SRC);

            if ($this->isBlacklistedNode($node, $src)) {
                continue;
            }

            if ($src) {
                // Make a noscript fallback
                $this->makeNoScript($node);

                // Set alternative src data
                $node->setAttribute(static::ATTR_DATA_SRC, $src);
                $node->removeAttribute(static::ATTR_SRC);
            }

            if ($src = $node->getAttribute(static::ATTR_SRCSET)) {
                $node->setAttribute(static::ATTR_DATA_SRCSET, $src);
                $node->removeAttribute(static::ATTR_SRCSET);
            }

            if ($this->empty_gif) {
                $node->setAttribute(static::ATTR_SRC, $this->empty_gif);
            } else {
                $this->setPlaceholderSrc($node);
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
            $src = $node->getAttribute(static::ATTR_SRC);

            if ($this->isBlacklistedNode($node, $src)) {
                continue;
            }

            if ($src) {
                // Make a noscript fallback
                $this->makeNoScript($node);

                // Set alternative src data
                $node->setAttribute(static::ATTR_DATA_SRC, $src);
                $node->removeAttribute(static::ATTR_SRC);
            }

            if ($this->empty_src) {
                $node->setAttribute(static::ATTR_SRC, $this->empty_src);
            }

            $this->addBackgroundColor($node);
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
     * @param  string  $src
     * @return bool
     */
    protected function isBlacklistedNode($node, $src = '')
    {
        if ($node->parentNode && $node->parentNode->nodeName == static::NOSCRIPT_TAG) {
            return false;
        }

        $blacklist = $this->do_not_optimize;

        if (is_array($blacklist)) {
            foreach ($blacklist as $pattern) {
                $regex = '#' . str_replace('#', '\#', $pattern) . '#';

                try {
                    if (preg_match($regex, $src . $node->textContent)) {
                        return true;
                    }
                } catch (Exception $e) {
                    unset($e);
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
                } catch (Exception $e) {
                    unset($e);
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
        if ($this->defer_web_fonts &&
            $this->isWebfontUrl($src) &&
            empty($node->getAttribute(static::ATTR_ONLOAD))) {
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
            if ($node->parentNode->nodeName === static::HEAD_TAG) {
                $this->body->appendChild($node);
            }

            $noscript = $this->dom->createElement(static::NOSCRIPT_TAG);
            $node->parentNode->insertBefore($noscript, $node->nextSibling);

            // Append normal image into the <noscript> tag
            $clone = $node->cloneNode();
            $noscript->appendChild($clone);

            // Cleanup
            $noscript = $clone = null;
        }
    }
}
