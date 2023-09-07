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

namespace AppSeeds\Helpers;

if (!defined('DEFER_PHP_ROOT')) {
    define('DEFER_PHP_ROOT', dirname(dirname(__DIR__)));
}

final class DeferConstant
{
    // Request argument for the library
    /**
     * @var string
     */
    const ARG_NODEFER = 'nodefer';

    /**
     * @var string
     */
    const ARG_DEBUG = 'debug';

    // -------------------------------------------------------------------------
    // Short copyright note
    /**
     * @var string
     */
    const TXT_DEBUG = 'DeferPHP Debug:';

    /**
     * @var string
     */
    const TXT_SHORT_COPYRIGHT = '[defer.php](https://code.shin.company/defer.php)';

    /**
     * @var string
     */
    const TXT_LONG_COPYRIGHT = PHP_EOL .
        '    ┌┬┐┌─┐┌─┐┌─┐┬─┐  ┬┌─┐' . PHP_EOL .
        '     ││├┤ ├┤ ├┤ ├┬┘  │└─┐' . PHP_EOL .
        '    ─┴┘└─┘└  └─┘┴└─o└┘└─┘' . PHP_EOL .
        'This page was optimized with defer.js' . PHP_EOL .
        'https://code.shin.company/defer.js' . PHP_EOL;

    // Default type for deferred script tags
    /**
     * @var string
     */
    const TXT_DEFAULT_DEFERJS = 'deferjs';

    // -------------------------------------------------------------------------
    // Attribute name for debugging
    /**
     * @var string
     */
    const ATTR_DEBUG = 'data-debug-id';

    // Attribute name to ignore optimizing the element
    /**
     * @var string
     */
    const ATTR_IGNORE = 'data-ignore';

    // Attribute name to ignore lazyloading the element
    /**
     * @var string
     */
    const ATTR_NOLAZY = 'data-nolazy';

    // Attribute names to lazyload CSS element
    /**
     * @var string
     */
    const ATTR_ASYNC = 'async';

    /**
     * @var string
     */
    const ATTR_DEFER = 'defer';

    /**
     * @var string
     */
    const ATTR_LAZY = 'data-lazy';

    // Native loading attribute for lazy-loading
    /**
     * @var string
     */
    const ATTR_LOADING = 'loading';

    // -------------------------------------------------------------------------
    // Class name will be inserted into the element
    // when a <noscript> tag for the element generated
    /**
     * @var string
     */
    const CLASS_HAS_FALLBACK = 'has-fallback';

    // Class name will be inserted into the html element
    /**
     * @var string
     */
    const CLASS_NO_DEFERJS = 'no-deferjs';

    // Class name will be inserted for fade-in animation
    /**
     * @var string
     */
    const CLASS_DEFER_LOADING = 'defer-loading';

    /**
     * @var string
     */
    const CLASS_DEFER_LOADED = 'defer-loaded';

    /**
     * @var string
     */
    const CLASS_DEFER_FADED = 'defer-faded';

    // -------------------------------------------------------------------------
    // Unify the media attribute from other related attributes
    /**
     * @var string[]
     */
    const UNIFY_MEDIA = [
        'data-media',
    ];

    // Unify the style attribute from other related attributes
    /**
     * @var string[]
     */
    const UNIFY_STYLE = [
        'data-style',
    ];

    // Unify the src attribute from other related attributes
    /**
     * @var string[]
     */
    const UNIFY_SRC = [
        'data-src',
        'data-lazy',
        'data-lazy-src',
        'data-original',
        'data-img-url',
    ];

    // Unify the srcset attribute from other related attributes
    /**
     * @var string[]
     */
    const UNIFY_SRCSET = [
        'data-srcset',
        'data-src-retina',
        'data-lazy-srcset',
    ];

    // Unify the sizes attribute from other related attributes
    /**
     * @var string[]
     */
    const UNIFY_SIZES = [
        'data-sizes',
        'data-lazy-sizes',
    ];

    // Unify the poster attribute from other related attributes
    /**
     * @var string[]
     */
    const UNIFY_POSTER = [
        'data-poster',
        'data-lazy-poster',
    ];

    // -------------------------------------------------------------------------
    // Global JS variable name to store delay time
    /**
     * @var string
     */
    const JS_GLOBAL_DELAY_VAR = 'DEFERJS_DELAY';

    // -------------------------------------------------------------------------
    // Template for SVG background
    /**
     * @var string
     */
    const TEMPLATE_SVG_PLACEHOLDER = 'data:image/svg+xml,'
                                    . "%%3Csvg xmlns='http://www.w3.org/2000/svg'"
                                    . " width='%s' height='%s'%%3E%%3C/svg%%3E";

    // Template for CSS grey background
    /**
     * @var string
     */
    const TEMPLATE_CSS_GREY = 'background-color:hsl(0,0%%,%d%%);';

    // Template for CSS colorfull background
    /**
     * @var string
     */
    const TEMPLATE_CSS_COLORFUL = 'background-color:hsl(%d,30%%,96%%);';

    // Template for lazyloading media attribute
    /**
     * @var string
     */
    const TEMPLATE_LAZY_MEDIA_ATTR = 'screen and (max-width:1px)';

    // Template for restoring media attribute
    /**
     * @var string
     */
    const TEMPLATE_RESTORE_MEDIA_ATTR = 'var self=this;defer(function(){self.media="%s"},2);';

    // Template for restoring media attribute
    /**
     * @var string
     */
    const TEMPLATE_RESTORE_STYLE_TAGS = 'var self=this;defer(function(){self.media="%s"},2);';

    // Template for restoring rel attribute
    /**
     * @var string
     */
    const TEMPLATE_RESTORE_REL_ATTR = 'rel="%s",removeAttribute("as"),removeAttribute("onload");';

    // Template for manually add defer script
    /**
     * @var string
     */
    const TEMPLATE_MANUALLY_ADD_DEFER = "'defer'in window||(window.defer=setTimeout,defer(function(){console.info('%s')}))";

    // Template for enabling splashscreen
    /**
     * @var string
     */
    const TEMPLATE_SPLASH_ENABLE = '<div id="deferjs-splash">' .
        '<style>#deferjs-splash{position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999}</style>%s' .
        '<noscript><style>#deferjs-splash{display:none}</style></noscript>' .
        '<script>defer(function(){document.getElementById("deferjs-splash").style.display="none"})</script>' .
        '</div>';

    // -------------------------------------------------------------------------
    // Source files
    /**
     * @var string
     */
    const SRC_DEFERJS_CDN = 'https://cdn.jsdelivr.net/npm/@shinsenter/defer.js@3.6.0/dist/defer_plus.min.js';

    /**
     * @var string
     */
    const SRC_POLYFILL_CDN = 'https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver';

    /**
     * @var string
     */
    const SRC_DEFERJS_FALLBACK = DEFER_PHP_ROOT . '/public/lib/defer_plus.min.js';

    /**
     * @var string
     */
    const SCR_DEFERJS_CACHE = DEFER_PHP_ROOT . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

    // -------------------------------------------------------------------------
    // For HTML minifier
    /**
     * @var string[]
     */
    const DOM_SPACE_IN = ['script', 'style', 'pre', 'textarea', 'code'];

    /**
     * @var string[]
     */
    const DOM_SPACE_AROUND = [
        'b', 'big', 'i', 'small', 'tt',
        'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var',
        'a', 'bdo', 'br', 'img', 'map', 'object', 'q', 'span', 'sub', 'sup',
        'button', 'input', 'label', 'select', 'textarea',
        'picture', 'figure', 'video', 'audio',
    ];

    // -------------------------------------------------------------------------
    // Some well-known third-party patterns
    // See: https://gist.github.com/lukecav/9931c3f6e402e23f58065d6b2665ef5b
    /**
     * @var string[]
     */
    const WELL_KNOWN_THIRDPARTY = [
        '.addthis.com',
        '.ampproject.org',
        '.bootstrapcdn.com',
        '.disqus.com',
        '.doubleclick.net',
        '.facebook.net',
        '.fontawesome.com',
        '.google-analytics.com',
        '.googlesyndication.com',
        '.googletagmanager.com',
        '.googletagservices.com',
        '.gravatar.com',
        '.gstatic.com',
        '.sharethis.com',
        '.twimg.com',
        '.wp.com',
        '.youtube.com',
        '//api.pinterest.com',
        '//apis.google.com',
        '//b.st-hatena.com',
        '//cdnjs.cloudflare.com',
        '//disqus.com',
        '//facebook.net',
        '//fonts.googleapis.com',
        '//google-analytics.com',
        '//googlesyndication.com',
        '//googletagmanager.com',
        '//googletagservices.com',
        '//maps.googleapis.com',
        '//platform.instagram.com',
        '//platform.linkedin.com',
        '//platform.twitter.com',
        '//s.w.org',
        '//s.yimg.',
        '//syndication.twitter.com',
        '//youtube.com',
        'adservice.google.',
    ];
}
