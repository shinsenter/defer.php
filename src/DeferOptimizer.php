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
        $this->addDeferJs();

        // Basic optimizations
        $this->optimizeCommentTags();
        $this->optimizeDnsTags();
        $this->optimizePreloadTags();
        $this->optimizeStyleTags();
        $this->optimizeScriptTags();
        $this->optimizeImgTags();
        $this->optimizeIframeTags();

        // Advanced optimizations
        $this->enablePreloading();
        $this->enableDnsPrefetch();
        $this->fixRenderBlocking();
        $this->minifyOutputHTML();
        $this->addFingerprint();
    }

    /*
    |--------------------------------------------------------------------------
    | For defer.js
    |--------------------------------------------------------------------------
     */

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

        $cleanup = '//script[@id="defer-js" or @id="defer-script"]|//link[@id="polyfill-js"]';

        foreach ($this->xpath->query($cleanup) as $node) {
            $node->parentNode->removeChild($node);
            $node = null;
        }

        $the_anchor = $this->head->childNodes->item(0);

        if ($this->append_defer_js) {
            if (empty(static::$deferjs_script)
                && file_exists(static::DEFERJS_CACHE)
                && time() - filemtime(static::DEFERJS_CACHE) < static::DEFERJS_EXPIRY) {
                require_once static::DEFERJS_CACHE;
            }

            if (empty(static::$deferjs_script)
                && !empty($source = @file_get_contents(static::DEFERJS_URL))) {
                static::$deferjs_script = '/* ' . static::DEFERJS_URL . ' */' . $source;
                $cache_template         = '<?php ' .
                    '/* https://github.com/shinsenter/defer.js cached on %s */ ' .
                    '\shinsenter\Defer::$deferjs_script = "%s";';

                @mkdir(dirname(static::DEFERJS_CACHE), 0755, true);
                @file_put_contents(
                    static::DEFERJS_CACHE,
                    sprintf($cache_template, date('Y-m-d H:i:s'), str_replace('"', '\"', static::$deferjs_script))
                );
            }
        } else {
            static::$deferjs_script = '';
        }

        // Append defer.js library loaded script is empty
        if (empty(static::$deferjs_script)) {
            $script_tag = $this->dom->createElement(static::SCRIPT_TAG);
            $script_tag->setAttribute(static::ATTR_SRC, static::DEFERJS_URL);
            $script_tag->setAttribute(static::ATTR_ID, 'defer-js');
            $this->head->insertBefore($script_tag, $the_anchor);
            $script_tag = null;
        }

        // Append defer.js helpers
        $script = static::$deferjs_script;
        $script .= implode(';', static::$loader_scripts);
        $script_tag = $this->dom->createElement(static::SCRIPT_TAG, trim($script));
        $script_tag->setAttribute(static::ATTR_ID, 'defer-script');
        $this->head->insertBefore($script_tag, $the_anchor);
        $script_tag = null;

        // Append simple effect for deferred contents
        $style_tag = $this->dom->createElement(static::STYLE_TAG, static::FADEIN_EFFECT);
        $this->head->appendChild($style_tag);
        $style_tag = null;

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
        if (empty(static::$fingerprint)
            && file_exists(static::FINGERPRINT_CACHE)
            && time() - filemtime(static::FINGERPRINT_CACHE) < static::DEFERJS_EXPIRY) {
            require_once static::FINGERPRINT_CACHE;
        }

        if (empty(static::$fingerprint)
            && !empty($source = @file_get_contents(static::FINGERPRINT_URL))) {
            static::$fingerprint = "\n" . $source . "\n";
            $cache_template      = '<?php \shinsenter\Defer::$fingerprint = "%s";';

            @mkdir(dirname(static::FINGERPRINT_CACHE), 0755, true);
            @file_put_contents(
                static::FINGERPRINT_CACHE,
                sprintf($cache_template, static::$fingerprint)
            );
        }

        $fingerprint = $this->dom->createComment(static::$fingerprint);
        $this->body->appendChild($fingerprint);
        $fingerprint = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Basic optimizations
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
                if ($this->defer_web_fonts &&
                    $this->isWebfontUrl($src) &&
                    empty($node->getAttribute(static::ATTR_ONLOAD))) {
                    $defer_script = 'var self=this;defer(function(){self.media="' .
                    addslashes($node->getAttribute(static::ATTR_MEDIA) ?: 'all') . '"},' .
                    $this->getDeferTime() . ')';

                    $node->setAttribute(static::ATTR_MEDIA, 'screen and (max-width: 1px)');
                    $node->setAttribute(static::ATTR_ONLOAD, $defer_script);
                }
            } else {
                $code = $node->textContent;

                // Strip comments
                // See: https://gist.github.com/orangexception/1292778
                $code = preg_replace('/\/\*(?:(?!\*\/).)*+\*\//', '', $code);

                // Minify the css code
                // See: https://gist.github.com/clipperhouse/1201239/cad48570925a4f5ff0579b654e865db97d73bcc4
                $code = preg_replace('/\s*([,>+;:!}{]{1})\s*/', '$1', $code);
                $code = str_replace(';}', '}', $code);

                $node->textContent = trim($code);
                $code              = null;
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
            $src = $node->getAttribute(static::ATTR_SRC);

            if ($this->isBlacklistedNode($node, $src)) {
                continue;
            }

            $code = $node->textContent;

            if ($this->enable_defer_scripts) {
                if (!empty($src)) {
                    $id = substr(md5($src), -8);
                    $node->removeAttribute(static::ATTR_SRC);
                    $node->removeAttribute(static::ATTR_DEFER);
                    $node->removeAttribute(static::ATTR_ASYNC);
                    $code = sprintf('deferscript(\'%s\',\'%s\',%d);', $src, $id, $this->getDeferTime());
                } else {
                    $code = $this->replaceJqueryOnload($code);
                    $code = $this->wrapWithDeferJs($code);
                }
            }

            if (!empty($code)) {
                $error = false;

                try {
                    $code = JsMin::minify($code);
                } catch (Exception $e) {
                    $error = true;
                }

                if (!$error) {
                    $node->textContent = $code;
                }

                $code = null;
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

        foreach ($this->img_cache as $i => $node) {
            $src = $node->getAttribute(static::ATTR_SRC);

            if ($this->isBlacklistedNode($node, $src)) {
                continue;
            }

            if (!empty($src)) {
                // Create noscript tag for normal image fallback
                if (!$this->debug_mode) {
                    $noscript = $this->dom->createElement(static::NOSCRIPT_TAG);
                    $node->parentNode->insertBefore($noscript, $node->nextSibling);

                    // Append normal image into the <noscript> tag
                    $clone = $node->cloneNode();
                    $noscript->appendChild($clone);

                    // Cleanup
                    $noscript = $clone = null;
                }

                // Append data-src into the image
                $node->setAttribute(static::ATTR_DATA_SRC, $src);
            }

            if (!empty($src = $node->getAttribute(static::ATTR_SRCSET))) {
                $node->setAttribute(static::ATTR_DATA_SRCSET, $src);
                $node->setAttribute(static::ATTR_SRCSET, $this->empty_gif);
            }

            if (!empty($this->empty_gif)) {
                $node->setAttribute(static::ATTR_SRC, $this->empty_gif);
            } else {
                $node->removeAttribute(static::ATTR_SRC);
                $node->removeAttribute(static::ATTR_SRCSET);

                // Add some placeholder color
                // https://github.com/axe312ger/sqip
                if ($this->use_color_placeholder) {
                    $placeholder = 'background-color:hsl(' . rand(1, 360) . ',100%,85%);';
                    $style       = (string) $node->getAttribute('style');
                    $node->setAttribute('style', $placeholder . $style);
                }
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

            if (!empty($src)) {
                $node->setAttribute(static::ATTR_DATA_SRC, $src);
                $node->setAttribute(static::ATTR_SRC, $this->empty_src);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Advanced optimizations
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
            $node->parentNode->removeChild($node);
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
            $node->parentNode->removeChild($node);
        }

        if (!empty($this->preload_map)) {
            $the_anchor = $this->head->childNodes->item(0);

            foreach ($this->preload_map as $url => $node) {
                $as = $this->getPreloadType($node);

                if (!empty($as) && !empty($url)) {
                    $link_tag = $this->dom->createElement(static::LINK_TAG);
                    $link_tag->setAttribute(static::ATTR_REL, static::REL_PRELOAD);
                    $link_tag->setAttribute(static::ATTR_AS, $as);
                    $link_tag->setAttribute(static::ATTR_HREF, $url);

                    $this->head->insertBefore($link_tag, $the_anchor);
                    $link_tag = null;
                }
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
            $node->parentNode->removeChild($node);
            $this->head->appendChild($node);
        }

        foreach ($this->script_cache as $node) {
            $node->parentNode->removeChild($node);
            $this->body->appendChild($node);
        }
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
            $node->nodeValue = '';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Other helper functions
    |--------------------------------------------------------------------------
     */

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
        if ($node->parentNode->nodeName == static::NOSCRIPT_TAG) {
            return false;
        }

        $blacklist = $this->do_not_optimize;

        if (is_array($blacklist)) {
            foreach ($blacklist as $pattern) {
                $regex = '/' . $pattern . '/';

                if (preg_match($regex, $src . $node->textContent)) {
                    return true;
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
                $regex = '/' . $pattern . '/';

                if (preg_match($regex, $src)) {
                    return true;
                }
            }
        }

        return false;
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
                        continue;
                    }
                    $as = static::PRELOAD_STYLE;
                    break;
                case static::STYLE_TAG:
                    $as = static::PRELOAD_STYLE;
                    break;
                case static::SCRIPT_TAG:
                    // $as = static::PRELOAD_SCRIPT;
                    continue;
                    break;
                case static::IMG_TAG:
                    // $as = static::PRELOAD_IMAGE;
                    continue;
                    break;
                case static::AUDIO_TAG:
                    // $as = static::PRELOAD_AUDIO;
                    continue;
                    break;
                case static::VIDEO_TAG:
                    // $as = static::PRELOAD_VIDEO;
                    continue;
                    break;
                case static::EMBED_TAG:
                    // $as = static::PRELOAD_EMBED;
                    continue;
                    break;
                case static::FRAME_TAG:
                case static::IFRAME_TAG:
                    // $as = static::PRELOAD_DOCUMENT;
                    continue;
                    break;
                default:
                    // $as = static::PRELOAD_FETCH;
                    continue;
                    break;
            }
        } elseif (is_string($node)) {
            $as = $node;
        }

        return $as;
    }

    /**
     * Replace the `jQuery(document).ready` calls with defer.js
     *
     * @since  1.0.0
     * @param  string $script
     * @return string
     */
    protected function replaceJqueryOnload($script)
    {
        $delay_time = $this->getDeferTime(500);

        $pattern = '/((jQuery|\$)\s*(\(\s*document\s*\)\.ready\s*)?\(\s*function\s*\([^\)]*\))/';

        if (preg_match_all($pattern, $script, $matches)) {
            $search  = $matches[1];
            $results = $this->searchToken($script, $search, '(', ')');

            foreach ($results as $original) {
                $replace = preg_replace($pattern, 'defer(function()', $original);
                $replace = preg_replace('/\s*\)$/', ',' . $delay_time . ')', $replace);
                $script  = str_replace($original, $replace, $script);
            }
        }

        $pattern = '/((jQuery|\$)\s*\(\s*document\s*\)\.ready\s*\(\s*)/';

        if (preg_match_all($pattern, $script, $matches)) {
            $search  = $matches[1];
            $results = $this->searchToken($script, $search, '(', ')');

            foreach ($results as $original) {
                $replace = preg_replace($pattern, 'defer(', $original);
                $replace = preg_replace('/\s*\)$/', ',' . $delay_time . ')', $replace);
                $script  = str_replace($original, $replace, $script);
            }
        }

        return $script;
    }

    /**
     * Wrap all self-invoke JavaScript functions with defer.js
     *
     * @since  1.0.0
     * @param  string $script
     * @return string
     */
    protected function wrapWithDeferJs($script)
    {
        $delay_time = $this->getDeferTime(500);
        $pattern    = '/((?<![\d\w\]\(])[!\(]*\s*function\s*\([^\)]*\)\s*{.*?}\s*[\)]?\s*\([^\)]*\)*)([;,]*)/';

        if (preg_match($pattern, $script)) {
            $replace = 'defer(function(){$1},' . $delay_time . ')$2';
            $script  = preg_replace($pattern, $replace, $script);
        }

        return $script;
    }

    /**
     * Internal utility for searching strings
     *
     * @since  1.0.0
     * @param  string $source
     * @param  array  $search
     * @param  string $startToken
     * @param  string $endToken
     * @param  string $startFrom
     * @return array
     */
    protected function searchToken($source, $search, $startToken = '{', $endToken = '}', $startFrom = 0)
    {
        $results = [];

        if (empty($search)) {
            return $results;
        }

        $keyword        = array_shift($search);
        $endTokenLength = strlen($endToken);

        $startPos = strpos($source, $keyword, $startFrom);
        $nextPos  = $startPos + strlen($keyword);
        $endPos   = 0;
        $counter  = 0;

        do {
            $startTokenPos = strpos($source, $startToken, $nextPos);
            $endTokenPos   = strpos($source, $endToken, $nextPos);

            switch (true) {
                case $startTokenPos !== false && $endTokenPos !== false:
                    $endPos = min($startTokenPos, $endTokenPos);
                    break;
                case $startTokenPos !== false:
                    $endPos = $startTokenPos;
                    break;
                case $endTokenPos !== false:
                    $endPos = $endTokenPos;
                    break;
                default:
                    $endPos = fasle;
                    break;
            }

            if ($endPos == $startTokenPos) {
                $counter++;
            } elseif ($endPos == $endTokenPos) {
                $counter--;
            } else {
                break;
            }

            $nextPos = $endPos + $endTokenLength;

            if ($counter < 0) {
                $counter   = 0;
                $endPos    = $nextPos;
                $results[] = substr($source, $startPos, $endPos - $startPos);

                if (count($search) > 0) {
                    $keyword  = array_shift($search);
                    $startPos = strpos($source, $keyword, $endPos);
                    $nextPos  = $startPos + strlen($keyword);
                } else {
                    break;
                }
            }
        } while ($startPos !== false);

        return $results;
    }
}
