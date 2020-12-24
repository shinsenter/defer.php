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

if (!defined('DEFER_JS_ROOT')) {
    define('DEFER_JS_ROOT', dirname(__DIR__));
}

if (!defined('DEFER_JS_VERSION')) {
    define('DEFER_JS_VERSION', 'latest');
}

if (!defined('DEFER_JS_CACHE_SUFFIX')) {
    define('DEFER_JS_CACHE_SUFFIX', '_' . DEFER_JS_VERSION);
}

if (!defined('DEFER_JS_CDN')) {
    define('DEFER_JS_CDN', 'https://cdn.jsdelivr.net/npm/@shinsenter/defer.js@' . DEFER_JS_VERSION);
}

if (!defined('DEFER_JS_IGNORE')) {
    define('DEFER_CSS_IGNORE', 'not(@data-ignore) and not(ancestor::noscript)');
    define('DEFER_JS_IGNORE', 'not(@data-ignore) and not(ancestor::noscript)');

    define('DEFER_IMG_IGNORE', implode(' and ', [
        DEFER_JS_IGNORE,
        'not(@src=\'\')',
        'not(starts-with(@src,"data:image"))',
    ]));

    define('DEFER_IMG_TAGS', implode(' or ', [
        '(self::input and @type="image")',
        'self::img',
        'self::picture',
        'self::source',
        'self::video',
        'self::audio',
    ]));

    define('DEFER_IFRAME_IGNORE', implode(' and ', [
        DEFER_JS_IGNORE,
        'not(@src=\'\')',
    ]));

    define('DEFER_IFRAME_TAGS', implode(' or ', [
        'self::iframe',
        'self::frame',
        'self::embed',
    ]));

    define('DEFER_MINIFY_HTML_IGNORE', 'not(parent::*[self::textarea or self::code or self::pre or self::script])');

    // Splash screen

    define('DEFER_SLASH_TEMPLATE', implode('', [
        '<style>#deferjs-splash{position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999}</style>',
        '<div id="deferjs-splash">%s</div>',
    ]));

    define('DEFER_SLASH_HIDE_SCRIPT', implode('', [
        '<noscript><style>#deferjs-splash{display:none}</style></noscript>',
        '<script>defer(function(){document.getElementById("deferjs-splash").style.display="none"},1)</script>',
    ]));
}

abstract class DeferInterface
{
    // For defer.js library
    const DEFERJS_CACHE  = __DIR__ . '/../cache/';
    const DEFERJS_URL    = DEFER_JS_CDN . '/dist/defer_plus.min.js';
    const HELPERS_URL    = DEFER_JS_ROOT . '/public/helpers.min.js';
    const INLINE_CSS_URL = DEFER_JS_ROOT . '/public/styles.min.css';

    // For splash screen
    const SLASH_TEMPLATE    = DEFER_SLASH_TEMPLATE;
    const SLASH_HIDE_SCRIPT = DEFER_SLASH_HIDE_SCRIPT;

    // Polyfill & library's fingerprint
    const POLYFILL_URL    = 'https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver';
    const FINGERPRINT_URL = 'https://raw.githubusercontent.com/shinsenter/defer.php/footprint/copyright.txt';

    // SVG placeholder
    const SVG_PLACEHOLDER = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 %d %d'></svg>";

    // Content tags
    const AUDIO_TAG    = 'audio';
    const BR_TAG       = 'br';
    const BODY_TAG     = 'body';
    const EMBED_TAG    = 'embed';
    const FRAME_TAG    = 'frame';
    const HEAD_TAG     = 'head';
    const IFRAME_TAG   = 'iframe';
    const IMG_TAG      = 'img';
    const INPUT_TAG    = 'input';
    const LINK_TAG     = 'link';
    const META_TAG     = 'meta';
    const NOSCRIPT_TAG = 'noscript';
    const PICTURE_TAG  = 'picture';
    const SCRIPT_TAG   = 'script';
    const SOURCE_TAG   = 'source';
    const STYLE_TAG    = 'style';
    const VIDEO_TAG    = 'video';

    // LINK tag's rel attribute
    const REL_DNSPREFETCH = 'dns-prefetch';
    const REL_PRECONNECT  = 'preconnect';
    const REL_PRELOAD     = 'preload';
    const REL_STYLESHEET  = 'stylesheet';

    // Types of content can be preloaded
    const PRELOAD_AUDIO    = 'audio';
    const PRELOAD_DOCUMENT = 'document';
    const PRELOAD_EMBED    = 'embed';
    const PRELOAD_FETCH    = 'fetch';
    const PRELOAD_FONT     = 'font';
    const PRELOAD_IMAGE    = 'image';
    const PRELOAD_OBJECT   = 'object';
    const PRELOAD_SCRIPT   = 'script';
    const PRELOAD_STYLE    = 'style';
    const PRELOAD_TRACK    = 'track';
    const PRELOAD_VIDEO    = 'video';
    const PRELOAD_WORKER   = 'worker';

    // Tag attributes
    const ATTR_ALT         = 'alt';
    const ATTR_AS          = 'as';
    const ATTR_ASYNC       = 'async';
    const ATTR_CHARSET     = 'charset';
    const ATTR_CLASS       = 'class';
    const ATTR_CONTENT     = 'content';
    const ATTR_CROSSORIGIN = 'crossorigin';
    const ATTR_DATA_IGNORE = 'data-ignore';
    const ATTR_DATA_NOLAZY = 'data-nolazy';
    const ATTR_DATA_SRC    = 'data-src';
    const ATTR_DATA_SRCSET = 'data-srcset';
    const ATTR_DATA_STYLE  = 'data-style';
    const ATTR_DEFER       = 'defer';
    const ATTR_HEIGHT      = 'height';
    const ATTR_HREF        = 'href';
    const ATTR_LANGUAGE    = 'language';
    const ATTR_ID          = 'id';
    const ATTR_MEDIA       = 'media';
    const ATTR_NAME        = 'name';
    const ATTR_ONLOAD      = 'onload';
    const ATTR_REL         = 'rel';
    const ATTR_SRC         = 'src';
    const ATTR_SRCSET      = 'srcset';
    const ATTR_SIZES       = 'sizes';
    const ATTR_STYLE       = 'style';
    const ATTR_TITLE       = 'title';
    const ATTR_TYPE        = 'type';
    const ATTR_WIDTH       = 'width';

    const UNIFY_OTHER_LAZY_SRC = [
        'data-src',
        'data-lazy',
        'data-lazy-src',
        'data-original',
    ];

    const UNIFY_OTHER_LAZY_SRCSET = [
        'data-srcset',
        'data-src-retina',
        'data-lazy-srcset',
    ];

    const UNIFY_OTHER_LAZY_SIZES = [
        'data-sizes',
        'data-lazy-sizes',
    ];

    // Xpath query expressions
    const COMMENT_XPATH    = '//comment()[not(contains(.,"[if ")) and not(contains(.,"[endif]"))]';
    const DNSCONN_XPATH    = '//link[@rel="dns-prefetch" or @rel="preconnect"]';
    const PRELOAD_XPATH    = '//link[@rel="preload"]';
    const STYLE_XPATH      = '//style[' . DEFER_CSS_IGNORE . ']|//link[' . DEFER_CSS_IGNORE . ' and @rel="stylesheet"]';
    const SCRIPT_XPATH     = '//script[' . DEFER_JS_IGNORE . ' and (not(@type) or contains(@type,"javascript"))]';
    const IMG_XPATH        = '//*[(' . DEFER_IMG_TAGS . ') and ' . DEFER_IMG_IGNORE . ']';
    const IFRAME_XPATH     = '//*[(' . DEFER_IFRAME_TAGS . ') and ' . DEFER_IFRAME_IGNORE . ']';
    const BACKGROUND_XPATH = '//*[' . DEFER_JS_IGNORE . ' and @style and contains(@style,"url")]';
    const NORMALIZE_XPATH  = '//text()[not(.=normalize-space(.))]';

    // Variable holders
    public static $deferjs_script = null;
    public static $fingerprint    = null;
    public static $helpers        = null;
    public static $inline_styles  = null;
}
