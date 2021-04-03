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

namespace AppSeeds\Helpers;

class DeferConstant
{
    // Request argument for the library
    const ARG_NODEFER = 'nodefer';
    const ARG_DEBUG   = 'debug';

    // -------------------------------------------------------------------------

    // Short copyright note
    const TXT_DEBUG           = 'DeferPHP Debug:';
    const TXT_SHORT_COPYRIGHT = '[defer.php](https://code.shin.company/defer.php)';
    const TXT_LONG_COPYRIGHT  = PHP_EOL .
        '    ┌┬┐┌─┐┌─┐┌─┐┬─┐  ┬┌─┐' . PHP_EOL .
        '     ││├┤ ├┤ ├┤ ├┬┘  │└─┐' . PHP_EOL .
        '    ─┴┘└─┘└  └─┘┴└─o└┘└─┘' . PHP_EOL .
        'This page was optimized with defer.js' . PHP_EOL .
        'https://code.shin.company/defer.js' . PHP_EOL;

    // Default type for deferred script tags
    const TXT_DEFAULT_DEFERJS = 'deferjs';

    // -------------------------------------------------------------------------

    // Attribute name for debugging
    const ATTR_DEBUG = 'data-debug-id';

    // Attribute name to ignore optimizing the element
    const ATTR_IGNORE = 'data-ignore';

    // Attribute name to ignore lazyloading the element
    const ATTR_NOLAZY = 'data-nolazy';

    // Attribute names to lazyload CSS element
    const ATTR_DEFER = 'defer';
    const ATTR_LAZY  = 'data-lazy';

    // Native loading attribute for lazy-loading
    const ATTR_LOADING = 'loading';

    // -------------------------------------------------------------------------

    // Class name will be inserted into the element
    // when a <noscript> tag for the element generated
    const CLASS_HAS_FALLBACK = 'has-fallback';

    // Class name will be inserted into the html element
    const CLASS_NO_DEFERJS = 'no-deferjs';

    // Class name will be inserted for fade-in animation
    const CLASS_DEFER_LOADING = 'defer-loading';
    const CLASS_DEFER_FADED   = 'defer-faded';

    // -------------------------------------------------------------------------

    // Unify the media attribute from other related attributes
    const UNIFY_MEDIA = [
        'data-media',
    ];

    // Unify the style attribute from other related attributes
    const UNIFY_STYLE = [
        'data-style',
    ];

    // Unify the src attribute from other related attributes
    const UNIFY_SRC = [
        'data-src',
        'data-lazy',
        'data-lazy-src',
        'data-original',
        'data-img-url',
    ];

    // Unify the srcset attribute from other related attributes
    const UNIFY_SRCSET = [
        'data-srcset',
        'data-src-retina',
        'data-lazy-srcset',
    ];

    // Unify the sizes attribute from other related attributes
    const UNIFY_SIZES = [
        'data-sizes',
        'data-lazy-sizes',
    ];

    // Unify the poster attribute from other related attributes
    const UNIFY_POSTER = [
        'data-poster',
        'data-lazy-poster',
    ];

    // -------------------------------------------------------------------------

    // Global JS variable name to store delay time
    const JS_GLOBAL_DELAY_VAR = 'DEFERJS_DELAY';

    // -------------------------------------------------------------------------

    // Template for SVG background
    const TEMPLATE_SVG_PLACEHOLDER = 'data:image/svg+xml,'
                                    . "%%3Csvg xmlns='http://www.w3.org/2000/svg'"
                                    . " width='%s' height='%s'%%3E%%3C/svg%%3E";

    // Template for CSS grey background
    const TEMPLATE_CSS_GREY = 'background-color:hsl(0,0%%,%d%%);';

    // Template for CSS colorfull background
    const TEMPLATE_CSS_COLORFUL = 'background-color:hsl(%d,30%%,96%%);';

    // Template for lazyloading media attribute
    const TEMPLATE_LAZY_MEDIA_ATTR = 'screen and (max-width:1px)';

    // Template for restoring media attribute
    const TEMPLATE_RESTORE_MEDIA_ATTR = 'var self=this;defer(function(){self.media="%s"},2);';

    // Template for restoring media attribute
    const TEMPLATE_RESTORE_STYLE_TAGS = 'var self=this;defer(function(){self.media="%s"},2);';

    // Template for restoring rel attribute
    const TEMPLATE_RESTORE_REL_ATTR = 'rel="%s",removeAttribute("as"),removeAttribute("onload");';

    // Template for manually add defer script
    const TEMPLATE_MANUALLY_ADD_DEFER = "'defer'in window||(window.defer=setTimeout,defer(function(){console.info('%s')}))";

    // Template for enabling splashscreen
    const TEMPLATE_SPLASH_ENABLE = '<div id="deferjs-splash">' .
        '<style>#deferjs-splash{position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999}</style>%s' .
        '<noscript><style>#deferjs-splash{display:none}</style></noscript>' .
        '<script>defer(function(){document.getElementById("deferjs-splash").style.display="none"})</script>' .
        '</div>';

    // -------------------------------------------------------------------------

    // Source files
    const SRC_DEFERJS_CDN      = 'https://cdn.jsdelivr.net/npm/@shinsenter/defer.js@2.4.0/dist/defer_plus.min.js';
    const SRC_POLYFILL_CDN     = 'https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver';
    const SRC_DEFERJS_FALLBACK = DEFER_PHP_ROOT . '/public/lib/defer_plus.min.js';
    const SCR_DEFERJS_CACHE    = DEFER_PHP_ROOT . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

    // -------------------------------------------------------------------------

    // For HTML minifier
    const DOM_SPACE_IN     = ['script', 'style', 'pre', 'textarea', 'code'];
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
    const WELL_KNOWN_THIRDPARTY = [
        '.addthis.com',
        '.ampproject.org',
        '.bootstrapcdn.com',
        '.disqus.com',
        '.doubleclick.net',
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
        '//cdnjs.cloudflare.com',
        '//connect.facebook.net',
        '//disqus.com',
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
