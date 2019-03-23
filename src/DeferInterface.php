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
    define('DEFER_JS_IGNORE', 'not(@data-ignore) and not(ancestor::*[@data-ignore]) and not(ancestor::noscript)');
}

abstract class DeferInterface
{
    // For defer.js library
    const DEFERJS_EXPIRY = 86400;
    const DEFERJS_CACHE  = __DIR__ . '/../cache/deferjs' . DEFER_JS_CACHE_SUFFIX . '.php';
    const DEFERJS_URL    = DEFER_JS_CDN . '/dist/defer_plus.min.js';
    const HELPERS_URL    = DEFER_JS_CDN . '/dist/helpers.min.js';

    // Library's fingerprint
    const FINGERPRINT_CACHE = __DIR__ . '/../cache/fingerprint' . DEFER_JS_CACHE_SUFFIX . '.php';
    const FINGERPRINT_URL   = 'https://raw.githubusercontent.com/shinsenter/defer.js/master/src/fingerprint';

    // Polyfill
    const POLYFILL_URL   = 'https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver';

    // Simple fade-in effect
    const FADEIN_EFFECT = 'html.no-deferjs img[data-src],html.no-deferjs iframe[data-src]{display:none!important}' .
        '[data-src],[data-srcset]{min-width:1px;min-height:1px;display:inline-block;max-width:100%;visibility:visible}' .
        '[data-lazied]{opacity:.1!important;transition:opacity .15s ease-in-out}' .
        '[data-lazied].in{background-color:transparent!important;opacity:1!important}';

    // Fake defer attribute for inline scripts
    const DEFER_INLINE = 'defer(function(){var e=window.document.head,r=defer_helper.h.querySelectorAll("script[type=deferscript]");[].forEach.call(r,function(r,t){r.parentNode.removeChild(r),r.type="text/javascript",e.appendChild(r)})},3)';

    // SVG placeholder
    const SVG_PLACEHOLDER = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 %d %d'></svg>";

    // Content tags
    const AUDIO_TAG    = 'audio';
    const EMBED_TAG    = 'embed';
    const FRAME_TAG    = 'frame';
    const IFRAME_TAG   = 'iframe';
    const IMG_TAG      = 'img';
    const LINK_TAG     = 'link';
    const NOSCRIPT_TAG = 'noscript';
    const SCRIPT_TAG   = 'script';
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
    const ATTR_CLASS       = 'class';
    const ATTR_CROSSORIGIN = 'crossorigin';
    const ATTR_DATA_IGNORE = 'data-ignore';
    const ATTR_DATA_SRC    = 'data-src';
    const ATTR_DATA_SRCSET = 'data-srcset';
    const ATTR_DEFER       = 'defer';
    const ATTR_HEIGHT      = 'height';
    const ATTR_HREF        = 'href';
    const ATTR_ID          = 'id';
    const ATTR_MEDIA       = 'media';
    const ATTR_ONLOAD      = 'onload';
    const ATTR_REL         = 'rel';
    const ATTR_SRC         = 'src';
    const ATTR_SRCSET      = 'srcset';
    const ATTR_STYLE       = 'style';
    const ATTR_TITLE       = 'title';
    const ATTR_TYPE        = 'type';
    const ATTR_WIDTH       = 'width';

    // Xpath query expressions
    const COMMENT_XPATH = '//comment()[not(contains(.,"[if ")) and not(contains(.,"[endif]"))]';
    const DNSCONN_XPATH = '//link[@rel="dns-prefetch" or @rel="preconnect"]';
    const PRELOAD_XPATH = '//link[@rel="preload"]';
    const STYLE_XPATH   = '//style[' . DEFER_JS_IGNORE . ']|//link[' . DEFER_JS_IGNORE . ' and @rel="stylesheet"]';
    const SCRIPT_XPATH  = '//script[' . DEFER_JS_IGNORE . ' and (not(@type) or contains(@type,"javascript"))]';
    const IMG_XPATH     = '//*[(local-name()="img" or local-name()="video" or local-name()="source") and ' . DEFER_JS_IGNORE . ' and not(@data-src) and not(ancestor::header)]';
    const IFRAME_XPATH  = '//*[(local-name()="iframe" or local-name()="frame" or local-name()="embed") and ' . DEFER_JS_IGNORE . ' and not(@data-src)]';

    // Variable holders
    public static $deferjs_script = null;
    public static $fingerprint    = null;
}
